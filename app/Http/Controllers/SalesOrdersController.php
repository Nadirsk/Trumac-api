<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Challan;
use App\Models\ChallanItem;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\JourneyPlan;
use App\Models\JourneyStop;
use App\Models\Notification;
use App\Models\PurchaseInvoice;
use App\Models\User;
use App\Models\RetailerLedger;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Retailer;
use App\Models\Sku;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class SalesOrdersController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'clear', 'restore', 'approve', 'reject', 'dispatch', 'deliver', 'cancel', 'confirmReceipt', 'downloadPdf']);
    }

    /**
     * Get all sales orders
     */
    public function index(Request $request)
    {
        try {
            $query = SalesOrder::with([
                'retailer:id,name,shop_name,phone',
                'items.sku:id,name,code',
                'createdBy:id,first_name,last_name',
            ])->withExists('invoice');

            // Special franchise filter: catch source_type=franchise AND legacy null source_type
            // orders where retailer.franchise_id matches
            $franchiseFilter = $request->input('search.franchise_id');
            if ($franchiseFilter) {
                $query->where(function ($q) use ($franchiseFilter) {
                    $q->where(function ($inner) use ($franchiseFilter) {
                        $inner->where('source_type', 'franchise')
                              ->where('source_id', $franchiseFilter);
                    })->orWhere(function ($inner) use ($franchiseFilter) {
                        $inner->whereNull('source_type')
                              ->whereHas('retailer', fn ($r) => $r->where('franchise_id', $franchiseFilter));
                    });
                });

                // Remove franchise_id from search so Utility doesn't try to filter on it
                $searchParams = $request->input('search', []);
                unset($searchParams['franchise_id']);
                $request->merge(['search' => $searchParams]);
            }

            $query = Utility::prepareSearchQuery($query, $request, new SalesOrder());
            $query = $query->orderBy('id', 'desc');
            $salesOrders = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title' => 'Sales Orders',
                'sub-title' => 'Sales orders fetched successfully',
                'success' => true,
                'data' => $salesOrders,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * Create a new sales order
     */
    public function store(Request $request)
    {
        // Check if this is a "No Order" record
        $isNoOrder = $request->status === 'no_order';

        $request->validate([
            'retailer_id' => 'required|exists:retailers,id',
            'source_type' => 'nullable|in:franchise,cg,warehouse',
            'source_id' => 'nullable|integer',
            'order_date' => 'nullable|date',
            'expected_delivery_date' => 'nullable|date',
            'status' => 'nullable|in:pending,approved,rejected,dispatched,delivered,cancelled,no_order',
            'no_order_reason' => $isNoOrder ? 'required|string|max:255' : 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'items' => $isNoOrder ? 'nullable|array' : 'required|array|min:1',
            'items.*.sku_id' => 'required|exists:skus,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        // Verify retailer belongs to company
        $retailer = Retailer::where('id', $request->retailer_id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$retailer) {
            return response()->json([
                'title' => 'Sales Order',
                'sub-title' => 'Retailer not found',
                'success' => false,
            ], 404);
        }

        // Auto-detect source (franchise/cg) from retailer mapping if not provided
        $sourceType = $request->source_type;
        $sourceId   = $request->source_id;
        if (!$sourceType) {
            if ($retailer->franchise_id) {
                $sourceType = 'franchise';
                $sourceId   = $retailer->franchise_id;
            } elseif ($retailer->company_godown_id) {
                $sourceType = 'cg';
                $sourceId   = $retailer->company_godown_id;
            }
        }

        // Create order
        $order = SalesOrder::create([
            'order_number' => SalesOrder::generateOrderNumber($request->company->id),
            'retailer_id' => $request->retailer_id,
            'source_type' => $sourceType,
            'source_id'   => $sourceId,
            'order_date' => $request->order_date ?? Carbon::today(),
            'expected_delivery_date' => $request->expected_delivery_date,
            'notes' => $request->notes,
            'status' => $request->status ?? SalesOrder::STATUS_PENDING,
            'no_order_reason' => $request->no_order_reason,
            'created_by' => $request->user()->id,
            'company_id' => $request->company->id,
        ]);

        // Skip items processing for "No Order" status
        if ($isNoOrder) {
            return response()->json([
                'title' => 'No Order Recorded',
                'sub-title' => 'No order record created successfully',
                'success' => true,
                'data' => $order->load(['retailer', 'createdBy:id,first_name,last_name']),
            ], 200);
        }

        // Add items
        $subtotal = 0;
        $totalTax = 0;
        $totalDiscount = 0;

        foreach ($request->items as $item) {
            $sku = Sku::find($item['sku_id']);
            $unitPrice = $item['unit_price'] ?? $sku->selling_price ?? 0;
            $quantity = $item['quantity'];
            $taxPercent = $sku->gst_percent ?? 0;
            $discountPercent = $item['discount_percent'] ?? 0;

            $itemSubtotal = $quantity * $unitPrice;
            $taxAmount = $itemSubtotal * ($taxPercent / 100);
            $discountAmount = $itemSubtotal * ($discountPercent / 100);
            $total = $itemSubtotal + $taxAmount - $discountAmount;

            SalesOrderItem::create([
                'sales_order_id' => $order->id,
                'sku_id' => $item['sku_id'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'total' => $total,
            ]);

            $subtotal += $itemSubtotal;
            $totalTax += $taxAmount;
            $totalDiscount += $discountAmount;
        }

        // Update order totals
        $order->update([
            'subtotal' => $subtotal,
            'tax_amount' => $totalTax,
            'discount_amount' => $totalDiscount,
            'total_amount' => $subtotal + $totalTax - $totalDiscount,
        ]);

        // Notify CG/Franchise Admin about new SO needing approval
        $sourceType = $order->source_type;
        $adminRoleId = $sourceType === 'cg' ? 6 : ($sourceType === 'franchise' ? 7 : null);
        if ($adminRoleId) {
            $admins = User::whereHas('position', fn($q) => $q->where('role_id', $adminRoleId))
                ->whereHas('companies', fn($q) => $q->where('companies.id', $request->company->id))
                ->pluck('id')->toArray();

            if (!empty($admins)) {
                $retailer = Retailer::find($order->retailer_id);
                Notification::sendToMany(
                    $admins,
                    'New SO: ' . $order->order_number,
                    'New Sales Order ' . $order->order_number . ' from ' . ($retailer->shop_name ?? $retailer->name ?? 'Retailer'),
                    'sales_order',
                    ['sales_order_id' => $order->id, 'action' => 'created'],
                    $request->company->id
                );
            }
        }

        return response()->json([
            'title' => 'Sales Order',
            'sub-title' => 'Order created successfully',
            'success' => true,
            'data' => $order->load(['retailer', 'items.sku', 'createdBy:id,first_name,last_name']),
        ], 200);
    }

    /**
     * Get a single sales order
     */
    public function show(Request $request)
    {
        $order = SalesOrder::with([
            'retailer',
            'items.sku',
            'createdBy:id,first_name,last_name',
            'approvedBy:id,first_name,last_name',
            'dispatchedBy:id,first_name,last_name',
            'deliveredBy:id,first_name,last_name',
        ])
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$order) {
            return response()->json([
                'title' => 'Sales Order',
                'sub-title' => 'Order not found',
                'success' => false,
            ], 404);
        }

        // Attach related challan (auto-generated on dispatch)
        $order->challan = Challan::where('sales_order_id', $order->id)
            ->where('company_id', $order->company_id)
            ->where('is_deleted', false)
            ->select('id', 'challan_number', 'status')
            ->first();

        // Attach related purchase invoice (auto-generated when retailer confirms receipt)
        $order->purchase_invoice = PurchaseInvoice::where('sales_order_id', $order->id)
            ->where('company_id', $order->company_id)
            ->where('is_deleted', false)
            ->select('id', 'invoice_number', 'status', 'total_amount')
            ->first();

        // Attach related sales invoice (with payment tracking)
        $order->invoice = Invoice::where('sales_order_id', $order->id)
            ->where('company_id', $order->company_id)
            ->where('is_deleted', false)
            ->where('status', '!=', Invoice::STATUS_CANCELLED)
            ->select('id', 'invoice_number', 'status', 'total', 'amount_paid', 'balance_due')
            ->first();

        return response()->json([
            'title' => 'Sales Order',
            'sub-title' => 'Order fetched successfully',
            'success' => true,
            'data' => $order,
        ], 200);
    }

    /**
     * Download sales order PDF
     */
    public function downloadPdf(Request $request)
    {
        $order = SalesOrder::with([
            'retailer',
            'items.sku',
            'createdBy:id,first_name,last_name',
            'approvedBy:id,first_name,last_name',
            'company',
        ])
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$order) {
            return response()->json([
                'title' => 'Sales Order',
                'sub-title' => 'Order not found',
                'success' => false,
            ], 404);
        }

        $amountInWords = $this->convertNumberToWords($order->total_amount);

        ini_set('memory_limit', '1G');
        $pdf = Pdf::loadView('pdfs.sales-order', [
            'salesOrder' => $order,
            'company' => $order->company,
            'retailer' => $order->retailer,
            'amountInWords' => 'Rupees ' . $amountInWords . ' Only',
        ]);

        return $pdf->download('sales-order-' . $order->order_number . '.pdf');
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

    /**
     * Update a sales order (only if pending/draft)
     */
    public function update(Request $request)
    {
        $order = SalesOrder::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$order) {
            return response()->json([
                'title' => 'Sales Order',
                'sub-title' => 'Order not found',
                'success' => false,
            ], 404);
        }

        if (!in_array($order->status, [SalesOrder::STATUS_DRAFT, SalesOrder::STATUS_PENDING])) {
            return response()->json([
                'title' => 'Sales Order',
                'sub-title' => 'Only draft or pending orders can be updated',
                'success' => false,
            ], 400);
        }

        $request->validate([
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
            'items' => 'nullable|array',
            'items.*.sku_id' => 'required|exists:skus,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $order->update([
            'expected_delivery_date' => $request->expected_delivery_date ?? $order->expected_delivery_date,
            'notes' => $request->notes ?? $order->notes,
        ]);

        // Update items if provided
        if ($request->has('items')) {
            // Delete existing items
            $order->items()->delete();

            // Add new items
            $subtotal = 0;
            $totalTax = 0;
            $totalDiscount = 0;

            foreach ($request->items as $item) {
                $sku = Sku::find($item['sku_id']);
                $unitPrice = $item['unit_price'] ?? $sku->selling_price ?? 0;
                $quantity = $item['quantity'];
                $taxPercent = $sku->gst_percent ?? 0;
                $discountPercent = $item['discount_percent'] ?? 0;

                $itemSubtotal = $quantity * $unitPrice;
                $taxAmount = $itemSubtotal * ($taxPercent / 100);
                $discountAmount = $itemSubtotal * ($discountPercent / 100);
                $total = $itemSubtotal + $taxAmount - $discountAmount;

                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'sku_id' => $item['sku_id'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'tax_percent' => $taxPercent,
                    'tax_amount' => $taxAmount,
                    'discount_percent' => $discountPercent,
                    'discount_amount' => $discountAmount,
                    'total' => $total,
                ]);

                $subtotal += $itemSubtotal;
                $totalTax += $taxAmount;
                $totalDiscount += $discountAmount;
            }

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $totalTax,
                'discount_amount' => $totalDiscount,
                'total_amount' => $subtotal + $totalTax - $totalDiscount,
            ]);
        }

        return response()->json([
            'title' => 'Sales Order',
            'sub-title' => 'Order updated successfully',
            'success' => true,
            'data' => $order->fresh(['retailer', 'items.sku']),
        ], 200);
    }

    /**
     * Approve a sales order
     */
    public function approve(Request $request)
    {
        $order = SalesOrder::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$order) {
            return response()->json([
                'title' => 'Sales Order',
                'sub-title' => 'Order not found',
                'success' => false,
            ], 404);
        }

        if ($order->status !== SalesOrder::STATUS_PENDING) {
            return response()->json([
                'title' => 'Sales Order',
                'sub-title' => 'Only pending orders can be approved',
                'success' => false,
            ], 400);
        }

        $order->update([
            'status' => SalesOrder::STATUS_APPROVED,
            'approved_by' => $request->user()->id,
            'approved_at' => Carbon::now(),
        ]);

        // Notify SO creator and retailer about approval
        $notifyIds = array_filter([$order->created_by]);
        $retailerUser = User::where('retailer_id', $order->retailer_id)->first();
        if ($retailerUser) $notifyIds[] = $retailerUser->id;

        foreach (array_unique($notifyIds) as $userId) {
            Notification::send(
                $userId,
                'SO Approved: ' . $order->order_number,
                'Sales Order ' . $order->order_number . ' has been approved.',
                'sales_order',
                ['sales_order_id' => $order->id, 'action' => 'approved'],
                $request->company->id
            );
        }

        return response()->json([
            'title' => 'Sales Order',
            'sub-title' => 'Order approved successfully',
            'success' => true,
            'data' => $order->fresh(['retailer', 'items.sku']),
        ], 200);
    }

    /**
     * Reject/Cancel a sales order
     */
    public function cancel(Request $request)
    {
        $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        $order = SalesOrder::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$order) {
            return response()->json([
                'title' => 'Sales Order',
                'sub-title' => 'Order not found',
                'success' => false,
            ], 404);
        }

        if (in_array($order->status, [SalesOrder::STATUS_DELIVERED, SalesOrder::STATUS_CANCELLED])) {
            return response()->json([
                'title' => 'Sales Order',
                'sub-title' => 'Order cannot be cancelled',
                'success' => false,
            ], 400);
        }

        $order->update([
            'status' => SalesOrder::STATUS_CANCELLED,
            'cancellation_reason' => $request->cancellation_reason,
        ]);

        return response()->json([
            'title' => 'Sales Order',
            'sub-title' => 'Order cancelled',
            'success' => true,
            'data' => $order,
        ], 200);
    }

    /**
     * E1.8b: Dispatch — check stock, deduct inventory, generate challan, assign driver, create journey stop
     */
    public function dispatch(Request $request)
    {
        $order = SalesOrder::with(['items.sku'])->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$order) {
            return response()->json(['title' => 'Sales Order', 'sub-title' => 'Order not found', 'success' => false], 404);
        }

        if (!in_array($order->status, [SalesOrder::STATUS_APPROVED, SalesOrder::STATUS_PROCESSING])) {
            return response()->json(['title' => 'Sales Order', 'sub-title' => 'Order must be approved before dispatch', 'success' => false], 400);
        }

        $request->validate([
            'driver_id'      => 'nullable|integer|exists:users,id',
            'vehicle_number' => 'nullable|string|max:50',
            'notes'          => 'nullable|string|max:1000',
        ]);

        // Determine source location type: franchise, company_godown, etc.
        $sourceLocationType = $order->source_type === 'cg' ? 'company_godown' : ($order->source_type ?? 'franchise');
        $sourceLocationLabel = $sourceLocationType === 'company_godown' ? 'company godown' : $sourceLocationType;

        // ── Step 1: Check source inventory for each item ─────────────────────
        $insufficientItems = [];
        foreach ($order->items as $item) {
            $inventory = Inventory::where('sku_id', $item->sku_id)
                ->where('location_type', $sourceLocationType)
                ->where('location_id', $order->source_id)
                ->where('company_id', $order->company_id)
                ->first();

            $available = $inventory ? $inventory->quantity : 0;
            if ($available < $item->quantity) {
                $insufficientItems[] = [
                    'sku'       => $item->sku?->name,
                    'required'  => $item->quantity,
                    'available' => $available,
                ];
            }
        }

        if (!empty($insufficientItems)) {
            return response()->json([
                'title'              => 'Sales Order',
                'sub-title'          => 'Insufficient stock in ' . $sourceLocationLabel . ' inventory',
                'success'            => false,
                'insufficient_items' => $insufficientItems,
            ], 422);
        }

        // ── Step 2: Deduct source inventory + record movements ───────────────
        foreach ($order->items as $item) {
            $inventory = Inventory::where('sku_id', $item->sku_id)
                ->where('location_type', $sourceLocationType)
                ->where('location_id', $order->source_id)
                ->where('company_id', $order->company_id)
                ->first();

            $inventory->deduct($item->quantity);

            InventoryMovement::create([
                'sku_id'            => $item->sku_id,
                'from_location_type'=> $sourceLocationType,
                'from_location_id'  => $order->source_id,
                'to_location_type'  => 'retailer',
                'to_location_id'    => $order->retailer_id,
                'quantity'          => $item->quantity,
                'movement_type'     => InventoryMovement::TYPE_SALE,
                'reference_type'    => 'sales_order',
                'reference_id'      => $order->id,
                'created_by'        => $request->user()->id,
                'company_id'        => $order->company_id,
            ]);
        }

        // ── Step 3: Generate Challan against the Sales Order ─────────────────
        $challan = Challan::create([
            'challan_number'    => Challan::generateChallanNumber($order->company_id),
            'sales_order_id'    => $order->id,
            'from_location_type'=> $sourceLocationType,
            'from_location_id'  => $order->source_id ?? 0,
            'to_location_type'  => 'retailer',
            'to_location_id'    => $order->retailer_id,
            'driver_id'         => $request->driver_id,
            'vehicle_number'    => $request->vehicle_number,
            'status'            => Challan::STATUS_GENERATED,
            'notes'             => $request->notes ?? 'Generated from Sales Order #' . $order->order_number,
            'created_by'        => $request->user()->id,
            'company_id'        => $order->company_id,
        ]);

        foreach ($order->items as $item) {
            ChallanItem::create([
                'challan_id'           => $challan->id,
                'sku_id'               => $item->sku_id,
                'sales_order_item_id'  => $item->id,
                'quantity'             => $item->quantity,
                'received_quantity'    => 0,
            ]);
        }

        // ── Step 4: Update order status ───────────────────────────────────────
        $order->update([
            'status'        => SalesOrder::STATUS_DISPATCHED,
            'dispatched_by' => $request->user()->id,
            'dispatched_at' => Carbon::now(),
            'driver_id'     => $request->driver_id,
        ]);

        // ── Step 5: Auto-create journey plan stop for driver ──────────────────
        $journeyPlan = null;
        if ($request->driver_id) {
            $journeyPlan = $this->createSalesOrderJourneyStop($order, $request);
            $challan->update(['journey_plan_id' => $journeyPlan?->id]);
        }

        // Notify retailer about dispatch
        $retailerUser = User::where('retailer_id', $order->retailer_id)->first();
        if ($retailerUser) {
            Notification::send(
                $retailerUser->id,
                'Order Dispatched: ' . $order->order_number,
                'Your order ' . $order->order_number . ' has been dispatched. Challan: ' . $challan->challan_number,
                'sales_order',
                ['sales_order_id' => $order->id, 'action' => 'dispatched', 'challan_id' => $challan->id],
                $order->company_id
            );
        }

        // Notify driver about delivery job
        if ($request->driver_id) {
            Notification::send(
                $request->driver_id,
                'New Delivery: ' . $challan->challan_number,
                'Challan ' . $challan->challan_number . ' assigned to you for delivery.',
                'challan',
                ['challan_id' => $challan->id, 'sales_order_id' => $order->id, 'action' => 'assigned'],
                $order->company_id
            );
        }

        return response()->json([
            'title'        => 'Sales Order',
            'sub-title'    => 'Order dispatched — challan generated',
            'success'      => true,
            'data'         => $order->fresh(['driver:id,first_name,last_name', 'items.sku']),
            'challan'      => ['id' => $challan->id, 'challan_number' => $challan->challan_number],
            'journey_plan' => $journeyPlan ? ['id' => $journeyPlan->id] : null,
        ], 200);
    }

    /**
     * F.3: Retailer confirms receipt with per-item quantities
     * Auto-generates Purchase Invoice when all items received (F.2b)
     */
    public function confirmReceipt(Request $request)
    {
        $order = SalesOrder::with('items')->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$order) {
            return response()->json(['title' => 'Sales Order', 'sub-title' => 'Order not found', 'success' => false], 404);
        }

        if ($order->status !== SalesOrder::STATUS_DISPATCHED) {
            return response()->json(['title' => 'Sales Order', 'sub-title' => 'Order is not in dispatched state', 'success' => false], 400);
        }

        $request->validate([
            'items'                        => 'required|array',
            'items.*.sales_order_item_id'  => 'required|integer',
            'items.*.received_quantity'    => 'required|numeric|min:0',
        ]);

        // Update per-item received quantities
        $allReceived = true;
        foreach ($request->items as $itemData) {
            $soItem = $order->items->firstWhere('id', $itemData['sales_order_item_id']);
            if (!$soItem) continue;

            $received = min($itemData['received_quantity'], $soItem->quantity);
            $soItem->update(['received_quantity' => $received]);

            if ($received < $soItem->quantity) {
                $allReceived = false;
            }
        }

        // Mark as delivered
        $order->update([
            'status'       => SalesOrder::STATUS_DELIVERED,
            'delivered_by' => $request->user()->id,
            'delivered_at' => Carbon::now(),
        ]);

        $freshOrder = $order->fresh();
        RetailerLedger::recordSale($freshOrder);
        $freshOrder->retailer()->increment('outstanding_amount', $freshOrder->total_amount);

        // F.2b: Auto-generate Purchase Invoice when all items received
        $invoice = null;
        if ($allReceived && class_exists(PurchaseInvoice::class)) {
            try {
                $invoice = PurchaseInvoice::createFromSalesOrder($order);
            } catch (\Throwable $e) {
                // Non-critical — don't fail the receipt
            }
        }

        return response()->json([
            'title'         => 'Sales Order',
            'sub-title'     => 'Receipt confirmed' . ($allReceived ? ' — Purchase Invoice generated' : ' (partial)'),
            'success'       => true,
            'all_received'  => $allReceived,
            'data'          => $order->fresh(['items.sku']),
            'invoice'       => $invoice ? ['id' => $invoice->id] : null,
        ], 200);
    }

    /**
     * Create / append to driver's journey plan for a Sales Order delivery
     */
    private function createSalesOrderJourneyStop(SalesOrder $order, Request $request): ?JourneyPlan
    {
        $retailer = Retailer::find($order->retailer_id);
        if (!$retailer) return null;

        // Reuse today's active journey plan for this driver or create new
        $journeyPlan = JourneyPlan::where('driver_id', $request->driver_id)
            ->where('company_id', $request->company->id)
            ->whereDate('date', now()->toDateString())
            ->where('is_deleted', false)
            ->whereIn('status', [JourneyPlan::STATUS_PLANNED, JourneyPlan::STATUS_STARTED])
            ->first();

        if (!$journeyPlan) {
            $journeyPlan = JourneyPlan::create([
                'driver_id'  => $request->driver_id,
                'date'       => now()->toDateString(),
                'status'     => JourneyPlan::STATUS_PLANNED,
                'notes'      => 'Delivery for Order #' . $order->order_number,
                'company_id' => $request->company->id,
            ]);
        }

        $nextSequence = ($journeyPlan->stops()->max('sequence') ?? 0) + 1;

        JourneyStop::create([
            'journey_plan_id' => $journeyPlan->id,
            'stop_type'       => JourneyStop::TYPE_DELIVERY,
            'reference_type'  => 'sales_order',
            'reference_id'    => $order->id,
            'sequence'        => $nextSequence,
            'name'            => $retailer->shop_name ?? $retailer->name ?? 'Retailer',
            'address'         => $retailer->address,
            'latitude'        => $retailer->latitude,
            'longitude'       => $retailer->longitude,
            'status'          => JourneyStop::STATUS_PENDING,
            'notes'           => 'Deliver Order #' . $order->order_number . ' to ' . ($retailer->shop_name ?? $retailer->name),
        ]);

        return $journeyPlan;
    }

    /**
     * Mark order as delivered
     */
    public function deliver(Request $request)
    {
        $order = SalesOrder::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$order) {
            return response()->json([
                'title' => 'Sales Order',
                'sub-title' => 'Order not found',
                'success' => false,
            ], 404);
        }

        if ($order->status !== SalesOrder::STATUS_DISPATCHED) {
            return response()->json([
                'title' => 'Sales Order',
                'sub-title' => 'Order must be dispatched before delivery',
                'success' => false,
            ], 400);
        }

        $order->update([
            'status' => SalesOrder::STATUS_DELIVERED,
            'delivered_by' => $request->user()->id,
            'delivered_at' => Carbon::now(),
        ]);

        $freshOrder = $order->fresh();
        RetailerLedger::recordSale($freshOrder);
        $freshOrder->retailer()->increment('outstanding_amount', $freshOrder->total_amount);

        // Notify retailer about delivery
        $retailerUser = User::where('retailer_id', $order->retailer_id)->first();
        if ($retailerUser) {
            Notification::send(
                $retailerUser->id,
                'Order Delivered: ' . $order->order_number,
                'Your order ' . $order->order_number . ' has been delivered.',
                'sales_order',
                ['sales_order_id' => $order->id, 'action' => 'delivered'],
                $order->company_id
            );
        }

        return response()->json([
            'title' => 'Sales Order',
            'sub-title' => 'Order delivered',
            'success' => true,
            'data' => $order,
        ], 200);
    }

    /**
     * Soft delete
     */
    public function clear(Request $request)
    {
        $order = SalesOrder::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$order) {
            return response()->json([
                'title' => 'Sales Order',
                'sub-title' => 'Order not found',
                'success' => false,
            ], 404);
        }

        $order->update(['is_deleted' => true]);

        return response()->json([
            'title' => 'Sales Order',
            'sub-title' => 'Order deleted successfully',
            'success' => true,
        ], 200);
    }

    /**
     * Get my orders (for current user)
     */
    public function myOrders(Request $request)
    {
        $query = SalesOrder::with([
            'retailer:id,name,shop_name',
            'items.sku:id,name,code',
        ])
            ->where('company_id', $request->company->id)
            ->where('created_by', $request->user()->id)
            ->where('is_deleted', false)
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $items = $query->limit(50)->get();

        return response()->json([
            'title' => 'My Sales Orders',
            'sub-title' => 'Orders fetched successfully',
            'success' => true,
            'count' => $items->count(),
            'data' => $items,
        ], 200);
    }

    /**
     * Get pending orders for approval
     */
    public function pendingApprovals(Request $request)
    {
        $query = SalesOrder::with([
            'retailer:id,name,shop_name,phone',
            'createdBy:id,first_name,last_name',
            'createdBy.position:id,name',
            'items.sku:id,name,code',
        ])
            ->where('company_id', $request->company->id)
            ->where('status', SalesOrder::STATUS_PENDING)
            ->where('is_deleted', false);

        $loggedInUser   = $request->user();
        $loggedInRoleId = $loggedInUser->position?->role_id;

        if ($loggedInRoleId === 7 && $loggedInUser->franchise_id) {
            // Franchise Admin — show orders from Retailer users mapped to their franchise
            $franchiseId = $loggedInUser->franchise_id;
            $query->where(function ($q) use ($franchiseId) {
                $q->where(function ($inner) use ($franchiseId) {
                    $inner->where('source_type', 'franchise')
                          ->where('source_id', $franchiseId);
                })->orWhere(function ($inner) use ($franchiseId) {
                    $inner->whereNull('source_type')
                          ->whereHas('retailer', fn ($r) => $r->where('franchise_id', $franchiseId));
                });
            });
        } elseif ($loggedInRoleId === 6 && $loggedInUser->company_godown_id) {
            // CG Admin — show orders from their CG
            $cgId = $loggedInUser->company_godown_id;
            $query->where(function ($q) use ($cgId) {
                $q->where(function ($inner) use ($cgId) {
                    $inner->where('source_type', 'cg')
                          ->where('source_id', $cgId);
                })->orWhere(function ($inner) use ($cgId) {
                    $inner->whereNull('source_type')
                          ->whereHas('retailer', fn ($r) => $r->where('company_godown_id', $cgId));
                });
            });
        } else {
            // IT Admin / others — show orders created by IT Employee
            $query->whereHas('createdBy.position', function ($q) {
                $q->where('name', 'IT Employee');
            });
        }

        $query->orderBy('order_date', 'asc');

        $items = $query->get();

        return response()->json([
            'title' => 'Pending Sales Orders',
            'sub-title' => 'Pending orders fetched',
            'success' => true,
            'count' => $items->count(),
            'data' => $items,
        ], 200);
    }

    /**
     * Get orders for a specific retailer
     */
    public function retailerOrders(Request $request, $retailerId)
    {
        $retailerId = $this->decryptParam($retailerId);

        $query = SalesOrder::with(['items.sku:id,name,code'])
            ->where('company_id', $request->company->id)
            ->where('retailer_id', $retailerId)
            ->where('is_deleted', false)
            ->orderBy('order_date', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from')) {
            $query->whereDate('order_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('order_date', '<=', $request->to);
        }

        $limit = $request->filled('itemsPerPage') ? (int) $request->itemsPerPage : 20;
        $items = $query->limit($limit)->get();

        return response()->json([
            'title' => 'Retailer Orders',
            'sub-title' => 'Orders fetched successfully',
            'success' => true,
            'count' => $items->count(),
            'data' => $items,
        ], 200);
    }

    /**
     * Reorder from previous order
     */
    public function reorder(Request $request, $orderId)
    {
        $previousOrder = SalesOrder::with('items')
            ->where('id', $orderId)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$previousOrder) {
            return response()->json([
                'title' => 'Sales Order',
                'sub-title' => 'Previous order not found',
                'success' => false,
            ], 404);
        }

        // Create new order with same items
        $newOrder = SalesOrder::create([
            'order_number' => SalesOrder::generateOrderNumber($request->company->id),
            'retailer_id' => $previousOrder->retailer_id,
            'source_type' => $previousOrder->source_type,
            'source_id' => $previousOrder->source_id,
            'order_date' => Carbon::today(),
            'status' => SalesOrder::STATUS_PENDING,
            'created_by' => $request->user()->id,
            'company_id' => $request->company->id,
        ]);

        $subtotal = 0;
        $totalTax = 0;
        $totalDiscount = 0;

        foreach ($previousOrder->items as $item) {
            $sku = Sku::find($item->sku_id);
            $unitPrice = $sku->selling_price ?? $item->unit_price;

            $itemSubtotal = $item->quantity * $unitPrice;
            $taxAmount = $itemSubtotal * ($item->tax_percent / 100);
            $discountAmount = $itemSubtotal * ($item->discount_percent / 100);
            $total = $itemSubtotal + $taxAmount - $discountAmount;

            SalesOrderItem::create([
                'sales_order_id' => $newOrder->id,
                'sku_id' => $item->sku_id,
                'quantity' => $item->quantity,
                'unit_price' => $unitPrice,
                'tax_percent' => $item->tax_percent,
                'tax_amount' => $taxAmount,
                'discount_percent' => $item->discount_percent,
                'discount_amount' => $discountAmount,
                'total' => $total,
            ]);

            $subtotal += $itemSubtotal;
            $totalTax += $taxAmount;
            $totalDiscount += $discountAmount;
        }

        $newOrder->update([
            'subtotal' => $subtotal,
            'tax_amount' => $totalTax,
            'discount_amount' => $totalDiscount,
            'total_amount' => $subtotal + $totalTax - $totalDiscount,
        ]);

        return response()->json([
            'title' => 'Sales Order',
            'sub-title' => 'Order created from previous order',
            'success' => true,
            'data' => $newOrder->load(['retailer', 'items.sku']),
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
}
