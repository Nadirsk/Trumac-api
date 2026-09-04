<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Grn;
use App\Models\GrnItem;
use App\Models\Notification;
use App\Models\PurchaseOrder;
use App\Models\Requisition;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GrnsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'complete', 'cancel']);
    }

    /**
     * Get all GRNs
     */
    public function index(Request $request)
    {
        try {
            $query = Grn::with([
                'items.sku:id,code,name,unit',
                'receiver:id,first_name,last_name',
            ]);
            $query = Utility::prepareSearchQuery($query, $request, new Grn());
            $query->orderBy('id', 'desc');
            $grns = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title' => 'GRNs',
                'sub-title' => 'GRNs fetched successfully',
                'success' => true,
                'data' => $grns,
                'count' => is_array($grns) ? count($grns) : $grns->count(),
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * Create GRN from Purchase Order
     */
    public function store(Request $request)
    {
        $request->validate([
            'reference_type' => 'required|string',
            'reference_id' => 'required|integer',
            'location_type' => 'required|string',
            'location_id' => 'nullable|integer',
            'received_date' => 'required|date',
            'received_by' => 'nullable|exists:users,id',
            'vehicle_number' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.sku_id' => 'required|exists:skus,id',
            'items.*.expected_quantity' => 'required|numeric|min:0',
            'items.*.received_quantity' => 'required|numeric|min:0',
            'items.*.rejected_quantity' => 'nullable|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.manufacturing_date' => 'nullable|date',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        // Validate reference exists
        if ($request->reference_type === 'purchase_order') {
            $po = PurchaseOrder::where('id', $request->reference_id)
                ->where('company_id', $request->company->id)
                ->whereIn('status', [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIAL])
                ->first();

            if (!$po) {
                return response()->json([
                    'title' => 'GRN',
                    'sub-title' => 'Invalid or unapproved purchase order',
                    'success' => false,
                ], 400);
            }
        }

        $grn = Grn::create([
            'grn_number' => Grn::generateGrnNumber($request->company->id),
            'reference_type' => $request->reference_type,
            'reference_id' => $request->reference_id,
            'location_type' => $request->location_type,
            'location_id' => $request->location_id,
            'received_by' => $request->received_by ?? $request->user()->id,
            'received_date' => $request->received_date,
            'status' => Grn::STATUS_DRAFT,
            'vehicle_number' => $request->vehicle_number,
            'driver_name' => $request->driver_name,
            'notes' => $request->notes,
            'company_id' => $request->company->id,
        ]);

        // Add items
        foreach ($request->items as $itemData) {
            $grn->items()->create([
                'sku_id' => $itemData['sku_id'],
                'expected_quantity' => $itemData['expected_quantity'],
                'received_quantity' => $itemData['received_quantity'],
                'rejected_quantity' => $itemData['rejected_quantity'] ?? 0,
                'batch_number' => $itemData['batch_number'] ?? null,
                'expiry_date' => $itemData['expiry_date'] ?? null,
                'manufacturing_date' => $itemData['manufacturing_date'] ?? null,
                'notes' => $itemData['notes'] ?? null,
                'status' => GrnItem::STATUS_PENDING,
            ]);
        }

        return response()->json([
            'title' => 'GRN',
            'sub-title' => 'GRN created',
            'success' => true,
            'data' => $grn->load(['items.sku:id,code,name,unit']),
        ], 200);
    }

    /**
     * Create GRN directly from PO (auto-populate items)
     */
    public function createFromPo(Request $request, $poId)
    {
        $po = PurchaseOrder::with('items.sku')
            ->where('id', $poId)
            ->where('company_id', $request->company->id)
            ->whereIn('status', [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIAL])
            ->first();

        if (!$po) {
            return response()->json([
                'title' => 'GRN',
                'sub-title' => 'Purchase order not found or not approved',
                'success' => false,
            ], 404);
        }

        $grn = Grn::create([
            'grn_number' => Grn::generateGrnNumber($request->company->id),
            'reference_type' => Grn::REF_PURCHASE_ORDER,
            'reference_id' => $po->id,
            'location_type' => $po->destination_type,
            'location_id' => $po->destination_id,
            'received_date' => now(),
            'status' => Grn::STATUS_DRAFT,
            'company_id' => $request->company->id,
        ]);

        // Create items from PO items (pending quantities only)
        foreach ($po->items as $poItem) {
            $pendingQty = $poItem->quantity - $poItem->received_quantity;
            if ($pendingQty > 0) {
                $grn->items()->create([
                    'sku_id' => $poItem->sku_id,
                    'expected_quantity' => $pendingQty,
                    'received_quantity' => 0,
                    'status' => GrnItem::STATUS_PENDING,
                ]);
            }
        }

        return response()->json([
            'title' => 'GRN',
            'sub-title' => 'GRN created from PO',
            'success' => true,
            'data' => $grn->load(['items.sku:id,code,name,unit']),
        ], 200);
    }

    /**
     * Get a single GRN
     */
    public function show(Request $request)
    {
        $grn = Grn::with([
            'items.sku:id,code,name,unit',
            'receiver:id,first_name,last_name',
        ])
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$grn) {
            return response()->json([
                'title' => 'GRN',
                'sub-title' => 'GRN not found',
                'success' => false,
            ], 404);
        }

        // Load reference
        $grn->reference = $grn->getReference();

        // Attach related challan (for requisition-based GRN)
        if ($grn->reference_type === \App\Models\Grn::REF_REQUISITION) {
            $grn->challan = \App\Models\Challan::where('requisition_id', $grn->reference_id)
                ->where('company_id', $grn->company_id)
                ->where('is_deleted', false)
                ->select('id', 'challan_number', 'status')
                ->first();

            // Attach related purchase invoice (auto-generated when GRN completes)
            $grn->purchase_invoice = \App\Models\PurchaseInvoice::where('grn_id', $grn->id)
                ->where('is_deleted', false)
                ->select('id', 'invoice_number', 'status', 'total_amount')
                ->first();
        }

        return response()->json([
            'title' => 'GRN',
            'sub-title' => 'GRN fetched successfully',
            'success' => true,
            'data' => $grn,
        ], 200);
    }

    /**
     * Update GRN items (quantities)
     */
    public function update(Request $request)
    {
        $grn = Grn::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$grn) {
            return response()->json([
                'title' => 'GRN',
                'sub-title' => 'GRN not found',
                'success' => false,
            ], 404);
        }

        if ($grn->status !== Grn::STATUS_DRAFT) {
            return response()->json([
                'title' => 'GRN',
                'sub-title' => 'Can only update draft GRNs',
                'success' => false,
            ], 400);
        }

        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:grn_items,id',
            'items.*.received_quantity' => 'required|numeric|min:0',
            'items.*.rejected_quantity' => 'nullable|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        foreach ($request->items as $itemData) {
            $item = $grn->items()->find($itemData['id']);
            if ($item) {
                $item->update([
                    'received_quantity' => $itemData['received_quantity'],
                    'rejected_quantity' => $itemData['rejected_quantity'] ?? 0,
                    'batch_number' => $itemData['batch_number'] ?? $item->batch_number,
                    'expiry_date' => $itemData['expiry_date'] ?? $item->expiry_date,
                    'notes' => $itemData['notes'] ?? $item->notes,
                ]);
            }
        }

        return response()->json([
            'title' => 'GRN',
            'sub-title' => 'GRN updated',
            'success' => true,
            'data' => $grn->fresh(['items.sku:id,code,name,unit']),
        ], 200);
    }

    /**
     * Complete GRN (finalize and update inventory)
     */
    public function complete(Request $request)
    {
        $grn = Grn::with('items')
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$grn) {
            return response()->json([
                'title' => 'GRN',
                'sub-title' => 'GRN not found',
                'success' => false,
            ], 404);
        }

        if ($grn->status !== Grn::STATUS_DRAFT) {
            return response()->json([
                'title' => 'GRN',
                'sub-title' => 'GRN already completed or cancelled',
                'success' => false,
            ], 400);
        }

        // Check if at least one item has received quantity
        $hasReceivedItems = $grn->items->sum('received_quantity') > 0;
        if (!$hasReceivedItems) {
            return response()->json([
                'title' => 'GRN',
                'sub-title' => 'At least one item must have received quantity',
                'success' => false,
            ], 400);
        }

        $grn->complete($request->user()->id);

        // Notify related users about stock update
        $this->notifyStockUpdate($grn, $request->company->id);

        return response()->json([
            'title' => 'GRN',
            'sub-title' => 'GRN completed and inventory updated',
            'success' => true,
            'data' => $grn->fresh(['items.sku:id,code,name,unit']),
        ], 200);
    }

    /**
     * Cancel GRN
     */
    public function cancel(Request $request)
    {
        $grn = Grn::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$grn) {
            return response()->json([
                'title' => 'GRN',
                'sub-title' => 'GRN not found',
                'success' => false,
            ], 404);
        }

        if ($grn->status === Grn::STATUS_COMPLETED) {
            return response()->json([
                'title' => 'GRN',
                'sub-title' => 'Cannot cancel completed GRN',
                'success' => false,
            ], 400);
        }

        $grn->update(['status' => Grn::STATUS_CANCELLED]);

        return response()->json([
            'title' => 'GRN',
            'sub-title' => 'GRN cancelled',
            'success' => true,
            'data' => $grn,
        ], 200);
    }

    /**
     * Get pending GRNs (draft)
     */
    public function pending(Request $request)
    {
        $items = Grn::with(['items.sku:id,code,name'])
            ->where('company_id', $request->company->id)
            ->where('status', Grn::STATUS_DRAFT)
            ->where('is_deleted', false)
            ->orderBy('received_date', 'desc')
            ->get();

        return response()->json([
            'title' => 'Pending GRNs',
            'sub-title' => 'Pending GRNs fetched',
            'success' => true,
            'count' => $items->count(),
            'data' => $items,
        ], 200);
    }

    /**
     * Notify related role users about stock update after GRN completion
     */
    private function notifyStockUpdate(Grn $grn, $companyId)
    {
        $locationLabel = ucfirst(str_replace('_', ' ', $grn->location_type ?? 'location'));
        $itemCount = $grn->items->count();
        $totalReceived = $grn->items->sum('received_quantity');

        // Determine which role manages this location
        $roleMap = [
            'head_office' => 4, // IT Admin
            'warehouse' => 5,   // Warehouse Admin
            'company_godown' => 6, // CG Admin
            'franchise' => 7,   // Franchise Admin
        ];

        $targetRoleId = $roleMap[$grn->location_type] ?? null;
        if (!$targetRoleId) return;

        // Find users of that role in this company
        $targetUsers = User::whereHas('position', function ($q) use ($targetRoleId) {
            $q->where('role_id', $targetRoleId);
        })
            ->whereHas('companies', function ($q) use ($companyId) {
                $q->where('companies.id', $companyId);
            })
            ->pluck('id')
            ->toArray();

        if (!empty($targetUsers)) {
            $refLabel = '';
            if ($grn->reference_type === 'purchase_order') {
                $po = PurchaseOrder::find($grn->reference_id);
                $refLabel = $po ? ' (PO: ' . $po->po_number . ')' : '';
            } elseif ($grn->reference_type === 'requisition') {
                $req = Requisition::find($grn->reference_id);
                $refLabel = $req ? ' (Req: ' . $req->requisition_number . ')' : '';
            }

            Notification::sendToMany(
                $targetUsers,
                'Stock Updated: GRN #' . $grn->grn_number,
                'GRN #' . $grn->grn_number . ' completed' . $refLabel . '. ' . $totalReceived . ' units of ' . $itemCount . ' item(s) received at ' . $locationLabel . '. Inventory updated.',
                'grn',
                [
                    'grn_id' => $grn->id,
                    'location_type' => $grn->location_type,
                    'location_id' => $grn->location_id,
                    'reference_type' => $grn->reference_type,
                    'reference_id' => $grn->reference_id,
                    'action' => 'stock_updated',
                ],
                $companyId
            );
        }
    }
}
