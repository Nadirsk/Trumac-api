<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Mail\PurchaseOrderMail;
use App\Models\Notification;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PurchaseOrdersController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'submit', 'approve', 'cancel', 'receive', 'clear', 'downloadPdf']);
    }

    /**
     * Get all purchase orders
     */
    public function index(Request $request)
    {
        try {
            $query = PurchaseOrder::with([
                'vendor:id,name,phone',
                'creator:id,first_name,last_name,phone',
                'items.sku:id,name,code',
            ]);
            $query = Utility::prepareSearchQuery($query, $request, new PurchaseOrder());
            $query = $query->orderBy('id', 'desc');
            $purchaseOrders = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title' => 'Purchase Orders',
                'sub-title' => 'Purchase orders fetched successfully',
                'success' => true,
                'data' => $purchaseOrders,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * Create a new purchase order
     */
    public function store(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date|after_or_equal:order_date',
            'destination_type' => 'required|string',
            'destination_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:1000',
            'terms' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.sku_id' => 'required|exists:skus,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        // Auto-pending for admin users
        $userRole = $request->user()->roles[0]->name ?? '';
        $isAdmin = in_array($userRole, ['ADMIN', 'SUPER ADMIN']);
        $status = $isAdmin ? PurchaseOrder::STATUS_PENDING : PurchaseOrder::STATUS_DRAFT;

        $purchaseOrder = PurchaseOrder::create([
            'po_number' => PurchaseOrder::generatePoNumber($request->company->id),
            'vendor_id' => $request->vendor_id,
            'order_date' => $request->order_date,
            'expected_date' => $request->expected_date,
            'status' => $status,
            'destination_type' => $request->destination_type,
            'destination_id' => $request->destination_id,
            'notes' => $request->notes,
            'terms' => $request->terms,
            'created_by' => $request->user()->id,
            'company_id' => $request->company->id,
        ]);

        // Add items
        foreach ($request->items as $itemData) {
            $quantity = $itemData['quantity'];
            $unitPrice = $itemData['unit_price'];
            $taxPercent = $itemData['tax_percent'] ?? 0;
            $discountPercent = $itemData['discount_percent'] ?? 0;

            // Calculate totals before creating
            $subtotal = $quantity * $unitPrice;
            $taxAmount = $subtotal * ($taxPercent / 100);
            $discountAmount = $subtotal * ($discountPercent / 100);
            $total = $subtotal + $taxAmount - $discountAmount;

            $purchaseOrder->items()->create([
                'sku_id' => $itemData['sku_id'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_percent' => $taxPercent,
                'discount_percent' => $discountPercent,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total' => $total,
            ]);
        }

        $purchaseOrder->calculateTotals();

        return response()->json([
            'title' => 'Purchase Order',
            'sub-title' => 'Purchase order created',
            'success' => true,
            'data' => $purchaseOrder->load(['vendor', 'items.sku']),
        ], 200);
    }

    /**
     * Get a single purchase order
     */
    public function show(Request $request)
    {
        $purchaseOrder = PurchaseOrder::with([
            'vendor',
            'items.sku:id,code,name,unit',
            'creator:id,first_name,last_name,phone',
            'approver:id,first_name,last_name',
        ])
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$purchaseOrder) {
            return response()->json([
                'title' => 'Purchase Order',
                'sub-title' => 'Purchase order not found',
                'success' => false,
            ], 404);
        }

        return response()->json([
            'title' => 'Purchase Order',
            'sub-title' => 'Purchase order fetched successfully',
            'success' => true,
            'data' => $purchaseOrder,
        ], 200);
    }

    /**
     * Update a purchase order
     */
    public function update(Request $request)
    {
        $purchaseOrder = PurchaseOrder::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$purchaseOrder) {
            return response()->json([
                'title' => 'Purchase Order',
                'sub-title' => 'Purchase order not found',
                'success' => false,
            ], 404);
        }

        if (!in_array($purchaseOrder->status, [PurchaseOrder::STATUS_DRAFT])) {
            return response()->json([
                'title' => 'Purchase Order',
                'sub-title' => 'Can only update draft orders',
                'success' => false,
            ], 400);
        }

        $request->validate([
            'vendor_id' => 'sometimes|exists:vendors,id',
            'expected_date' => 'nullable|date',
            'destination_type' => 'sometimes|string',
            'destination_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:1000',
            'terms' => 'nullable|string|max:1000',
            'items' => 'sometimes|array|min:1',
        ]);

        $purchaseOrder->update($request->only([
            'vendor_id',
            'expected_date',
            'destination_type',
            'destination_id',
            'notes',
            'terms'
        ]));

        // Update items if provided
        if ($request->filled('items')) {
            // Delete existing items
            $purchaseOrder->items()->delete();

            // Add new items
            foreach ($request->items as $itemData) {
                $item = $purchaseOrder->items()->create([
                    'sku_id' => $itemData['sku_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'tax_percent' => $itemData['tax_percent'] ?? 0,
                    'discount_percent' => $itemData['discount_percent'] ?? 0,
                ]);
                $item->calculateTotal();
            }

            $purchaseOrder->calculateTotals();
        }

        return response()->json([
            'title' => 'Purchase Order',
            'sub-title' => 'Purchase order updated',
            'success' => true,
            'data' => $purchaseOrder->fresh(['vendor', 'items.sku']),
        ], 200);
    }

    /**
     * Submit PO for approval
     */
    public function submit(Request $request)
    {
        $purchaseOrder = PurchaseOrder::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$purchaseOrder) {
            return response()->json([
                'title' => 'Purchase Order',
                'sub-title' => 'Purchase order not found',
                'success' => false,
            ], 404);
        }

        if ($purchaseOrder->status !== PurchaseOrder::STATUS_DRAFT) {
            return response()->json([
                'title' => 'Purchase Order',
                'sub-title' => 'Only draft orders can be submitted',
                'success' => false,
            ], 400);
        }

        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_PENDING]);

        // Notify IT Admin about PO needing approval
        $itAdmins = User::whereHas('position', fn($q) => $q->where('role_id', 4))
            ->whereHas('companies', fn($q) => $q->where('companies.id', $request->company->id))
            ->where('id', '!=', $request->user()->id)
            ->pluck('id')->toArray();

        if (!empty($itAdmins)) {
            Notification::sendToMany(
                $itAdmins,
                'PO Approval Needed: ' . $purchaseOrder->po_number,
                'Purchase Order ' . $purchaseOrder->po_number . ' submitted for your approval.',
                'purchase_order',
                ['purchase_order_id' => $purchaseOrder->id, 'action' => 'submitted'],
                $request->company->id
            );
        }

        return response()->json([
            'title' => 'Purchase Order',
            'sub-title' => 'Purchase order submitted for approval',
            'success' => true,
            'data' => $purchaseOrder,
        ], 200);
    }

    /**
     * Approve a purchase order
     */
    public function approve(Request $request)
    {
        $purchaseOrder = PurchaseOrder::with(['vendor', 'items.sku', 'company'])
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        // Manually load destination based on type
        if ($purchaseOrder && $purchaseOrder->destination_type && $purchaseOrder->destination_id) {
            switch ($purchaseOrder->destination_type) {
                case 'warehouse':
                    $purchaseOrder->destination = \App\Models\Warehouse::find($purchaseOrder->destination_id);
                    break;
                case 'franchise':
                    $purchaseOrder->destination = \App\Models\Franchise::find($purchaseOrder->destination_id);
                    break;
                case 'retailer':
                    $purchaseOrder->destination = \App\Models\Retailer::find($purchaseOrder->destination_id);
                    break;
                case 'head_office':
                    // For head office, use the company itself
                    $purchaseOrder->destination = $purchaseOrder->company;
                    break;
            }
        }

        if (!$purchaseOrder) {
            return response()->json([
                'title' => 'Purchase Order',
                'sub-title' => 'Purchase order not found',
                'success' => false,
            ], 404);
        }

        if (!$purchaseOrder->canApprove()) {
            return response()->json([
                'title' => 'Purchase Order',
                'sub-title' => 'Purchase order cannot be approved',
                'success' => false,
            ], 400);
        }

        // Unset dynamic destination attribute before saving to avoid SQL error
        unset($purchaseOrder->destination);

        $purchaseOrder->approve($request->user()->id);

        // Reload destination for response/email
        if ($purchaseOrder->destination_type && $purchaseOrder->destination_id) {
            switch ($purchaseOrder->destination_type) {
                case 'warehouse':
                    $purchaseOrder->destination = \App\Models\Warehouse::find($purchaseOrder->destination_id);
                    break;
                case 'franchise':
                    $purchaseOrder->destination = \App\Models\Franchise::find($purchaseOrder->destination_id);
                    break;
                case 'retailer':
                    $purchaseOrder->destination = \App\Models\Retailer::find($purchaseOrder->destination_id);
                    break;
                case 'head_office':
                    $purchaseOrder->destination = $purchaseOrder->company;
                    break;
            }
        }

        // Send email to vendor
        try {
            if ($purchaseOrder->vendor && $purchaseOrder->vendor->email) {
                Mail::to($purchaseOrder->vendor->email)->send(new PurchaseOrderMail($purchaseOrder));
            }
        } catch (Exception $e) {
            \Log::error('Failed to send PO email: ' . $e->getMessage());
        }

        // Notify PO creator about approval
        if ($purchaseOrder->created_by) {
            Notification::send(
                $purchaseOrder->created_by,
                'PO Approved: ' . $purchaseOrder->po_number,
                'Your Purchase Order ' . $purchaseOrder->po_number . ' has been approved.',
                'purchase_order',
                ['purchase_order_id' => $purchaseOrder->id, 'action' => 'approved'],
                $request->company->id
            );
        }

        return response()->json([
            'title' => 'Purchase Order',
            'sub-title' => 'Purchase order approved and email sent to vendor',
            'success' => true,
            'data' => $purchaseOrder,
        ], 200);
    }

    /**
     * Cancel a purchase order
     */
    public function cancel(Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $purchaseOrder = PurchaseOrder::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$purchaseOrder) {
            return response()->json([
                'title' => 'Purchase Order',
                'sub-title' => 'Purchase order not found',
                'success' => false,
            ], 404);
        }

        if ($purchaseOrder->status === PurchaseOrder::STATUS_RECEIVED) {
            return response()->json([
                'title' => 'Purchase Order',
                'sub-title' => 'Cannot cancel received orders',
                'success' => false,
            ], 400);
        }

        $purchaseOrder->update([
            'status' => PurchaseOrder::STATUS_CANCELLED,
            'notes' => $purchaseOrder->notes . "\nCancellation reason: " . $request->reason,
        ]);

        return response()->json([
            'title' => 'Purchase Order',
            'sub-title' => 'Purchase order cancelled',
            'success' => true,
            'data' => $purchaseOrder,
        ], 200);
    }

    /**
     * Receive goods for a purchase order
     */
    public function receive(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:purchase_order_items,id',
            'items.*.received_quantity' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $purchaseOrder = PurchaseOrder::with('items')
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$purchaseOrder) {
            return response()->json([
                'title' => 'Purchase Order',
                'sub-title' => 'Purchase order not found',
                'success' => false,
            ], 404);
        }

        if (!in_array($purchaseOrder->status, [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIAL])) {
            return response()->json([
                'title' => 'Purchase Order',
                'sub-title' => 'Can only receive approved or partially received orders',
                'success' => false,
            ], 400);
        }

        // Update received quantities for each item
        foreach ($request->items as $itemData) {
            $item = PurchaseOrderItem::with('sku:id,name')
                ->where('id', $itemData['item_id'])
                ->where('purchase_order_id', $purchaseOrder->id)
                ->first();

            if ($item) {
                $newReceivedQuantity = ($item->received_quantity ?? 0) + $itemData['received_quantity'];

                // Prevent receiving more than ordered
                if ($newReceivedQuantity > $item->quantity) {
                    return response()->json([
                        'title' => 'Purchase Order',
                        'sub-title' => 'Cannot receive more than ordered quantity',
                        'success' => false,
                        'error' => "Item " . ($item->sku ? $item->sku->name : 'ID ' . $item->id) . " exceeds ordered quantity",
                    ], 400);
                }

                $item->update(['received_quantity' => $newReceivedQuantity]);
            }
        }

        // Add notes if provided
        if ($request->filled('notes')) {
            $purchaseOrder->update([
                'notes' => $purchaseOrder->notes . "\nReceiving notes: " . $request->notes,
            ]);
        }

        // Update PO status based on received quantities
        $purchaseOrder->fresh(['items'])->updateReceivedStatus();

        return response()->json([
            'title' => 'Purchase Order',
            'sub-title' => 'Goods received successfully',
            'success' => true,
            'data' => $purchaseOrder->fresh(['vendor', 'items.sku']),
        ], 200);
    }

    /**
     * Get my purchase orders
     */
    public function myOrders(Request $request)
    {
        $query = PurchaseOrder::with(['vendor:id,name'])
            ->where('company_id', $request->company->id)
            ->where('created_by', $request->user()->id)
            ->where('is_deleted', false)
            ->orderBy('order_date', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $items = $query->limit(50)->get();

        return response()->json([
            'title' => 'My Purchase Orders',
            'sub-title' => 'Orders fetched',
            'success' => true,
            'count' => $items->count(),
            'data' => $items,
        ], 200);
    }

    /**
     * Get pending approvals
     */
    public function pendingApprovals(Request $request)
    {
        $query = PurchaseOrder::with([
            'vendor:id,name,phone',
            'creator:id,first_name,last_name,phone',
            'items.sku:id,name,code',
        ])
            ->where('company_id', $request->company->id)
            ->where('status', PurchaseOrder::STATUS_PENDING)
            ->where('is_deleted', false)
            ->orderBy('order_date', 'desc');

        $items = Utility::getSearchRequestQueryResults($request, $query);

        return response()->json([
            'title' => 'Pending Approvals',
            'sub-title' => 'Pending POs fetched',
            'success' => true,
            'data' => $items,
        ], 200);
    }

    /**
     * Get approved POs pending receipt
     */
    public function pendingReceipt(Request $request)
    {
        $items = PurchaseOrder::with([
            'vendor:id,name',
            'items.sku:id,code,name',
        ])
            ->where('company_id', $request->company->id)
            ->whereIn('status', [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIAL])
            ->where('is_deleted', false)
            ->orderBy('expected_date', 'asc')
            ->get();

        return response()->json([
            'title' => 'Pending Receipt',
            'sub-title' => 'POs pending receipt fetched',
            'success' => true,
            'count' => $items->count(),
            'data' => $items,
        ], 200);
    }

    /**
     * Soft delete
     */
    public function clear(Request $request)
    {
        $purchaseOrder = PurchaseOrder::find($request->id);
        if ($purchaseOrder) {
            $purchaseOrder->update(['is_deleted' => true]);
        }

        return response()->json([
            'title' => 'Purchase Order',
            'sub-title' => 'Purchase order deleted',
            'success' => true,
        ], 200);
    }

    /**
     * Download PO as PDF
     */
    public function downloadPdf(Request $request)
    {
        $purchaseOrder = PurchaseOrder::with([
            'vendor',
            'items.sku',
            'creator',
            'approver',
            'company',
            'requisition:id,requisition_number',
        ])
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$purchaseOrder) {
            return response()->json([
                'title' => 'Purchase Order',
                'sub-title' => 'Purchase order not found',
                'success' => false,
            ], 404);
        }

        // Load destination
        if ($purchaseOrder->destination_type && $purchaseOrder->destination_type !== 'head_office' && $purchaseOrder->destination_id) {
            $modelMap = [
                'warehouse' => \App\Models\Warehouse::class,
                'company_godown' => \App\Models\CompanyGodown::class,
                'franchise' => \App\Models\Franchise::class,
            ];
            $modelClass = $modelMap[$purchaseOrder->destination_type] ?? null;
            if ($modelClass) {
                $purchaseOrder->destination = $modelClass::find($purchaseOrder->destination_id);
            }
        }

        $amountInWords = $this->convertNumberToWords($purchaseOrder->total_amount);

        ini_set('memory_limit', '1G');
        $pdf = Pdf::loadView('pdfs.purchase-order', [
            'purchaseOrder' => $purchaseOrder,
            'amountInWords' => 'Rupees ' . $amountInWords . ' Only',
        ]);

        return $pdf->download($purchaseOrder->po_number . '.pdf');
    }

    /**
     * Convert number to words
     */
    private function convertNumberToWords($number): string
    {
        $number = round($number, 2);
        $wholeNumber = (int) $number;
        $decimal = round(($number - $wholeNumber) * 100);

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
            'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
            'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $convert = function ($num) use ($ones, $tens, &$convert) {
            if ($num < 20) return $ones[$num];
            if ($num < 100) return $tens[(int)($num / 10)] . ($num % 10 ? ' ' . $ones[$num % 10] : '');
            if ($num < 1000) return $ones[(int)($num / 100)] . ' Hundred' . ($num % 100 ? ' and ' . $convert($num % 100) : '');
            if ($num < 100000) return $convert((int)($num / 1000)) . ' Thousand' . ($num % 1000 ? ' ' . $convert($num % 1000) : '');
            if ($num < 10000000) return $convert((int)($num / 100000)) . ' Lakh' . ($num % 100000 ? ' ' . $convert($num % 100000) : '');
            return $convert((int)($num / 10000000)) . ' Crore' . ($num % 10000000 ? ' ' . $convert($num % 10000000) : '');
        };

        $result = $wholeNumber > 0 ? $convert($wholeNumber) : 'Zero';
        if ($decimal > 0) {
            $result .= ' and ' . $convert($decimal) . ' Paise';
        }

        return $result;
    }
}
