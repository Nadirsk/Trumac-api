<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\CashCollection;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\PaymentReceived;
use App\Models\RetailerLedger;
use App\Models\Retailer;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class CashCollectionsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'collect', 'reschedule']);
    }

    /**
     * Get all cash collections
     */
    public function index(Request $request)
    {
        try {
            $query = CashCollection::query();
            $query = Utility::prepareSearchQuery($query, $request, new CashCollection());
            $query->orderBy('id', 'desc');
            $collections = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title' => 'Cash Collections',
                'sub-title' => 'Collections fetched successfully',
                'success' => true,
                'data' => $collections,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * Create a new cash collection entry
     */
    public function store(Request $request)
    {
        $request->validate([
            'retailer_id' => 'required|exists:retailers,id,is_deleted,0',
            'sales_order_id' => 'nullable|exists:sales_orders,id',
            'amount_due' => 'required|numeric|min:0.01',
            'collection_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:500',
        ]);

        $collection = CashCollection::create([
            'retailer_id' => $request->retailer_id,
            'sales_order_id' => $request->sales_order_id,
            'amount_due' => $request->amount_due,
            'collection_date' => $request->collection_date,
            'status' => CashCollection::STATUS_PENDING,
            'notes' => $request->notes,
            'company_id' => $request->company->id,
        ]);

        return response()->json([
            'title' => 'Cash Collection',
            'sub-title' => 'Collection entry created successfully',
            'success' => true,
            'data' => $collection->load(['retailer', 'salesOrder']),
        ], 200);
    }

    /**
     * Get a single collection
     */
    public function show(Request $request)
    {
        $collection = CashCollection::with([
            'retailer',
            'salesOrder',
            'collector:id,first_name,last_name',
        ])
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$collection) {
            return response()->json([
                'title' => 'Cash Collection',
                'sub-title' => 'Collection not found',
                'success' => false,
            ], 404);
        }

        return response()->json([
            'title' => 'Cash Collection',
            'sub-title' => 'Collection fetched successfully',
            'success' => true,
            'data' => $collection,
        ], 200);
    }

    /**
     * Record a collection
     */
    public function collect(Request $request)
    {
        $request->validate([
            'amount_collected' => 'required|numeric|min:0',
            'payment_mode' => 'required|in:cash,upi,cheque,bank_transfer',
            'payment_reference' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string|max:500',
        ]);

        $collection = CashCollection::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$collection) {
            return response()->json([
                'title' => 'Cash Collection',
                'sub-title' => 'Collection not found',
                'success' => false,
            ], 404);
        }

        if ($collection->status === CashCollection::STATUS_COLLECTED) {
            return response()->json([
                'title' => 'Cash Collection',
                'sub-title' => 'Collection already recorded',
                'success' => false,
            ], 400);
        }

        $amountCollected = $request->amount_collected;
        $originalAmountDue = $collection->amount_due;
        $status = $amountCollected >= $originalAmountDue
            ? CashCollection::STATUS_COLLECTED
            : CashCollection::STATUS_PARTIAL;

        // Calculate remaining amount for partial payments
        $remainingAmount = $status === CashCollection::STATUS_PARTIAL
            ? $originalAmountDue - $amountCollected
            : 0;

        $updateData = [
            'amount_collected' => $amountCollected,
            'collected_by' => $request->user()->id,
            'status' => $status,
            'payment_mode' => $request->payment_mode,
            'payment_reference' => $request->payment_reference,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'notes' => $request->notes,
        ];

        // Update amount_due to remaining amount for partial payments
        if ($status === CashCollection::STATUS_PARTIAL) {
            $updateData['amount_due'] = $remainingAmount;
        }

        $collection->update($updateData);

        // Record in ledger
        RetailerLedger::recordCollection($collection);

        // Update retailer's outstanding amount
        $collection->retailer()->decrement('outstanding_amount', $amountCollected);

        // Auto-link cash collection to retailer's unpaid invoices
        // Distribute collected amount across oldest unpaid invoices (FIFO)
        $paymentsLinked = $this->linkCollectionToInvoices(
            $collection->retailer_id,
            $amountCollected,
            $request->payment_mode,
            $request->payment_reference,
            $request->user()->id,
            $request->company->id
        );

        return response()->json([
            'title' => 'Cash Collection',
            'sub-title' => 'Collection recorded successfully' . ($paymentsLinked > 0 ? " ({$paymentsLinked} invoice(s) updated)" : ''),
            'success' => true,
            'data' => $collection->fresh(['retailer', 'collector:id,first_name,last_name']),
            'payments_linked' => $paymentsLinked,
        ], 200);
    }

    /**
     * Reschedule a collection
     */
    public function reschedule(Request $request)
    {
        $request->validate([
            'next_date' => 'required|date|after:today',
            'reason' => 'required|string|max:500',
        ]);

        $collection = CashCollection::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$collection) {
            return response()->json([
                'title' => 'Cash Collection',
                'sub-title' => 'Collection not found',
                'success' => false,
            ], 404);
        }

        if ($collection->status === CashCollection::STATUS_COLLECTED) {
            return response()->json([
                'title' => 'Cash Collection',
                'sub-title' => 'Cannot reschedule completed collection',
                'success' => false,
            ], 400);
        }

        $collection->reschedule($request->next_date, $request->reason);

        $message = $collection->status === CashCollection::STATUS_ESCALATED
            ? 'Collection escalated to Team Leader'
            : 'Collection rescheduled';

        // If escalated (3 reschedules), notify IT Team Leaders
        if ($collection->status === CashCollection::STATUS_ESCALATED) {
            $retailer = Retailer::find($collection->retailer_id);
            $retailerName = $retailer->shop_name ?? $retailer->name ?? 'Unknown';

            // Find IT Team Leaders (position with role_id=4 and position name containing 'Team Leader')
            $teamLeaders = User::whereHas('position', function ($q) {
                $q->where('role_id', 4)->where('name', 'like', '%Team Leader%');
            })
                ->whereHas('companies', fn($q) => $q->where('companies.id', $request->company->id))
                ->pluck('id')->toArray();

            if (!empty($teamLeaders)) {
                Notification::sendToMany(
                    $teamLeaders,
                    'Cash Collection Escalated: ' . $retailerName,
                    'Retailer "' . $retailerName . '" has rescheduled cash collection 3 times. Escalated to you.',
                    'cash_collection',
                    ['cash_collection_id' => $collection->id, 'retailer_id' => $collection->retailer_id, 'action' => 'escalated'],
                    $request->company->id
                );
            }
        }

        return response()->json([
            'title' => 'Cash Collection',
            'sub-title' => $message,
            'success' => true,
            'data' => $collection->fresh(['retailer']),
        ], 200);
    }

    /**
     * Get today's collections for current user
     */
    public function todayCollections(Request $request)
    {
        $collections = CashCollection::with([
            'retailer:id,name,shop_name,phone,address',
            'salesOrder:id,order_number,total_amount',
            'collector:id,first_name,last_name',
        ])
            ->where('company_id', $request->company->id)
            ->where('is_deleted', false)
            ->where(function ($query) {
                $query->whereDate('collection_date', today())
                    ->orWhereDate('next_collection_date', today());
            })
            ->whereIn('status', [CashCollection::STATUS_PENDING, CashCollection::STATUS_RESCHEDULED, CashCollection::STATUS_PARTIAL])
            ->orderBy('collection_date', 'asc')
            ->get();

        return response()->json([
            'title' => 'Today\'s Collections',
            'sub-title' => 'Collections fetched',
            'success' => true,
            'count' => $collections->count(),
            'data' => $collections,
        ], 200);
    }

    /**
     * Get my collections (for current user)
     */
    public function myCollections(Request $request)
    {
        $query = CashCollection::with([
            'retailer:id,name,shop_name',
            'salesOrder:id,order_number',
        ])
            ->where('company_id', $request->company->id)
            ->where('collected_by', $request->user()->id)
            ->where('is_deleted', false)
            ->orderBy('collection_date', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $items = $query->limit(50)->get();

        return response()->json([
            'title' => 'My Collections',
            'sub-title' => 'Collections fetched',
            'success' => true,
            'count' => $items->count(),
            'data' => $items,
        ], 200);
    }

    /**
     * Get escalated collections (for Team Leader)
     */
    public function escalated(Request $request)
    {
        $collections = CashCollection::with([
            'retailer:id,name,shop_name,phone,address,is_flagged',
            'salesOrder:id,order_number,total_amount',
            'collector:id,first_name,last_name',
        ])
            ->where('company_id', $request->company->id)
            ->where('status', CashCollection::STATUS_ESCALATED)
            ->where('is_deleted', false)
            ->orderBy('collection_date', 'asc')
            ->get();

        return response()->json([
            'title' => 'Escalated Collections',
            'sub-title' => 'Escalated collections fetched',
            'success' => true,
            'count' => $collections->count(),
            'data' => $collections,
        ], 200);
    }

    /**
     * Get retailer statement
     */
    public function retailerStatement(Request $request, $retailerId)
    {
        $retailerId = $this->decryptParam($retailerId);

        $retailer = Retailer::where('id', $retailerId)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$retailer) {
            return response()->json([
                'title' => 'Retailer Statement',
                'sub-title' => 'Retailer not found',
                'success' => false,
            ], 404);
        }

        $balance = RetailerLedger::getRetailerBalance($retailerId);
        $transactions = RetailerLedger::getStatement(
            $retailerId,
            $request->from_date,
            $request->to_date
        );

        // Get past commitments (cash collections)
        $pastCommitments = CashCollection::where('retailer_id', $retailerId)
            ->where('company_id', $request->company->id)
            ->where('is_deleted', false)
            ->with(['collector:id,first_name,last_name'])
            ->orderBy('collection_date', 'desc')
            ->limit(20)
            ->get();

        // Calculate summary
        $totalDue = CashCollection::where('retailer_id', $retailerId)
            ->where('company_id', $request->company->id)
            ->where('is_deleted', false)
            ->sum('amount_due');

        $totalCollected = CashCollection::where('retailer_id', $retailerId)
            ->where('company_id', $request->company->id)
            ->where('is_deleted', false)
            ->sum('amount_collected');

        $pendingCount = CashCollection::where('retailer_id', $retailerId)
            ->where('company_id', $request->company->id)
            ->where('is_deleted', false)
            ->whereIn('status', [CashCollection::STATUS_PENDING, CashCollection::STATUS_RESCHEDULED, CashCollection::STATUS_PARTIAL])
            ->count();

        $totalReschedules = CashCollection::where('retailer_id', $retailerId)
            ->where('company_id', $request->company->id)
            ->where('is_deleted', false)
            ->sum('reschedule_count');

        return response()->json([
            'title' => 'Retailer Statement',
            'sub-title' => 'Statement fetched',
            'success' => true,
            'data' => [
                'retailer' => $retailer,
                'current_balance' => $balance,
                'transactions' => $transactions,
                'past_commitments' => $pastCommitments,
                'summary' => [
                    'total_due' => $totalDue,
                    'total_collected' => $totalCollected,
                    'pending_count' => $pendingCount,
                    'total_reschedules' => $totalReschedules,
                ],
            ],
        ], 200);
    }

    /**
     * Get collection summary
     */
    public function summary(Request $request)
    {
        $query = CashCollection::where('company_id', $request->company->id)
            ->where('is_deleted', false);

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('collection_date', [$request->from_date, $request->to_date]);
        } else {
            $query->whereDate('collection_date', today());
        }

        $summary = [
            'total_due' => $query->sum('amount_due'),
            'total_collected' => $query->where('status', CashCollection::STATUS_COLLECTED)->sum('amount_collected'),
            'pending_count' => (clone $query)->whereIn('status', [CashCollection::STATUS_PENDING, CashCollection::STATUS_RESCHEDULED])->count(),
            'collected_count' => (clone $query)->where('status', CashCollection::STATUS_COLLECTED)->count(),
            'escalated_count' => (clone $query)->where('status', CashCollection::STATUS_ESCALATED)->count(),
        ];

        return response()->json([
            'title' => 'Collection Summary',
            'sub-title' => 'Summary fetched',
            'success' => true,
            'data' => $summary,
        ], 200);
    }

    private function decryptParam($encrypted)
    {
        $decoded = str_replace(['-', '_'], ['+', '/'], urldecode($encrypted));
        $secretKey = '12345678123456781234567812345678';
        $iv = 'Ef7ix7ETPgghl3vP';
        $decrypted = openssl_decrypt(base64_decode($decoded), 'AES-256-CBC', $secretKey, OPENSSL_RAW_DATA, $iv);
        return $decrypted !== false ? $decrypted : $encrypted;
    }

    /**
     * Auto-link cash collection to retailer's unpaid invoices (FIFO - oldest first)
     * Distributes collected amount across multiple invoices if needed
     */
    private function linkCollectionToInvoices($retailerId, $amountCollected, $paymentMode, $paymentReference, $userId, $companyId): int
    {
        $remaining = $amountCollected;
        $paymentsLinked = 0;

        // Get retailer's unpaid invoices (oldest first)
        $unpaidInvoices = Invoice::where('retailer_id', $retailerId)
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->whereIn('status', [Invoice::STATUS_DRAFT, Invoice::STATUS_SENT, Invoice::STATUS_PARTIALLY_PAID, Invoice::STATUS_OVERDUE])
            ->where('balance_due', '>', 0)
            ->orderBy('invoice_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($unpaidInvoices as $invoice) {
            if ($remaining <= 0) break;

            // Pay as much as possible against this invoice
            $payAmount = min($remaining, $invoice->balance_due);

            // Create PaymentReceived record
            PaymentReceived::recordPayment($invoice, [
                'amount' => $payAmount,
                'payment_mode' => $paymentMode ?? 'cash',
                'payment_date' => now()->toDateString(),
                'reference_number' => $paymentReference,
                'notes' => 'Auto-linked from cash collection',
            ], $userId, $companyId);

            // Notify retailer about payment
            $retailerUser = User::where('retailer_id', $retailerId)->first();
            if ($retailerUser) {
                Notification::send(
                    $retailerUser->id,
                    'Payment Received: Rs.' . number_format($payAmount, 2),
                    'Payment of Rs.' . number_format($payAmount, 2) . ' recorded against Invoice ' . $invoice->invoice_number,
                    'payment',
                    ['invoice_id' => $invoice->id, 'action' => 'received'],
                    $companyId
                );
            }

            $remaining -= $payAmount;
            $paymentsLinked++;
        }

        return $paymentsLinked;
    }
}
