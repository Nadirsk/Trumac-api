<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\JourneyPlan;
use App\Models\JourneyStop;
use App\Models\Challan;
use App\Models\ChallanItem;
use App\Models\Notification;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RequisitionsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'approve', 'reject', 'cancel', 'fulfill', 'submit']);
    }

    /**
     * Get all requisitions
     */
    public function index(Request $request)
    {
        try {
            $query = Requisition::with([
                'items.sku:id,code,name,unit',
                'requester:id,first_name,last_name',
                'approver:id,first_name,last_name',
            ])
                ->withCount('items');
            $query = Utility::prepareSearchQuery($query, $request, new Requisition());
            $query->orderBy('id', 'desc');
            $requisitions = Utility::getSearchRequestQueryResults($request, $query);

            // Append from/to location objects
            $items = $requisitions instanceof \Illuminate\Pagination\LengthAwarePaginator
                ? $requisitions->getCollection()
                : $requisitions;

            $items->each(function ($req) {
                $req->from_location = $req->getFromLocation();
                $req->to_location = $req->getToLocation();
            });

            return response()->json([
                'title' => 'Requisitions',
                'sub-title' => 'Requisitions fetched successfully',
                'success' => true,
                'data' => $requisitions,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * Create a new requisition
     */
    public function store(Request $request)
    {
        $request->validate([
            'from_location_type' => 'required|string',
            'from_location_id' => 'nullable|integer',
            'to_location_type' => 'required|string',
            'to_location_id' => 'required|integer',
            'required_date' => 'nullable|date',
            'requested_by' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.sku_id' => 'required|exists:skus,id',
            'items.*.requested_quantity' => 'required|numeric|min:0.01',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        $requisition = Requisition::create([
            'requisition_number' => Requisition::generateRequisitionNumber($request->company->id),
            'from_location_type' => $request->from_location_type,
            'from_location_id' => $request->from_location_id,
            'to_location_type' => $request->to_location_type,
            'to_location_id' => $request->to_location_id,
            'status' => Requisition::STATUS_DRAFT,
            'request_date' => now(),
            'required_date' => $request->required_date,
            'requested_by' => $request->requested_by ?? $request->user()->id,
            'notes' => $request->notes,
            'company_id' => $request->company->id,
        ]);

        // Add items
        foreach ($request->items as $itemData) {
            $requisition->items()->create([
                'sku_id' => $itemData['sku_id'],
                'requested_quantity' => $itemData['requested_quantity'],
                'notes' => $itemData['notes'] ?? null,
            ]);
        }

        return response()->json([
            'title' => 'Requisition',
            'sub-title' => 'Requisition created',
            'success' => true,
            'data' => $requisition->load(['items.sku:id,code,name,unit']),
        ], 200);
    }

    /**
     * Get a single requisition
     */
    public function show(Request $request)
    {
        $requisition = Requisition::with([
            'items.sku:id,code,name,unit',
            'requester:id,first_name,last_name',
            'approver:id,first_name,last_name',
            'challans:id,requisition_id,challan_number,status',
        ])
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$requisition) {
            return response()->json([
                'title' => 'Requisition',
                'sub-title' => 'Requisition not found',
                'success' => false,
            ], 404);
        }

        // Load location info
        $requisition->from_location = $requisition->getFromLocation();
        $requisition->to_location = $requisition->getToLocation();

        return response()->json([
            'title' => 'Requisition',
            'sub-title' => 'Requisition fetched successfully',
            'success' => true,
            'data' => $requisition,
        ], 200);
    }

    /**
     * Update a requisition
     */
    public function update(Request $request)
    {
        $requisition = Requisition::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$requisition) {
            return response()->json([
                'title' => 'Requisition',
                'sub-title' => 'Requisition not found',
                'success' => false,
            ], 404);
        }

        if (!in_array($requisition->status, [Requisition::STATUS_DRAFT])) {
            return response()->json([
                'title' => 'Requisition',
                'sub-title' => 'Can only update draft requisitions',
                'success' => false,
            ], 400);
        }

        $request->validate([
            'required_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'sometimes|array|min:1',
        ]);

        $requisition->update($request->only(['required_date', 'notes']));

        // Update items if provided
        if ($request->filled('items')) {
            // Delete existing items
            $requisition->items()->delete();

            // Add new items
            foreach ($request->items as $itemData) {
                $requisition->items()->create([
                    'sku_id' => $itemData['sku_id'],
                    'requested_quantity' => $itemData['requested_quantity'],
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }
        }

        return response()->json([
            'title' => 'Requisition',
            'sub-title' => 'Requisition updated',
            'success' => true,
            'data' => $requisition->fresh(['items.sku:id,code,name,unit']),
        ], 200);
    }

    /**
     * Submit requisition for approval
     */
    public function submit(Request $request)
    {
        $requisition = Requisition::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$requisition) {
            return response()->json([
                'title' => 'Requisition',
                'sub-title' => 'Requisition not found',
                'success' => false,
            ], 404);
        }

        if ($requisition->status !== Requisition::STATUS_DRAFT) {
            return response()->json([
                'title' => 'Requisition',
                'sub-title' => 'Only draft requisitions can be submitted',
                'success' => false,
            ], 400);
        }

        $requisition->update(['status' => Requisition::STATUS_PENDING]);

        // Notify admin about requisition needing approval
        $locationRoleMap = ['head_office' => 4, 'warehouse' => 5, 'company_godown' => 6, 'franchise' => 7];
        $adminRoleId = $locationRoleMap[$requisition->from_location_type] ?? null;
        if ($adminRoleId) {
            $admins = User::whereHas('position', fn($q) => $q->where('role_id', $adminRoleId))
                ->whereHas('companies', fn($q) => $q->where('companies.id', $request->company->id))
                ->where('id', '!=', $request->user()->id)
                ->pluck('id')->toArray();

            if (!empty($admins)) {
                Notification::sendToMany(
                    $admins,
                    'Requisition Approval: ' . $requisition->requisition_number,
                    'Requisition ' . $requisition->requisition_number . ' submitted for your approval.',
                    'requisition',
                    ['requisition_id' => $requisition->id, 'action' => 'submitted'],
                    $request->company->id
                );
            }
        }

        return response()->json([
            'title' => 'Requisition',
            'sub-title' => 'Requisition submitted for approval',
            'success' => true,
            'data' => $requisition,
        ], 200);
    }

    /**
     * Approve a requisition
     */
    public function approve(Request $request)
    {
        $request->validate([
            'items' => 'nullable|array',
            'items.*.id' => 'required_with:items|exists:requisition_items,id',
            'items.*.approved_quantity' => 'required_with:items|numeric|min:0',
        ]);

        $requisition = Requisition::with('items')
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$requisition) {
            return response()->json([
                'title' => 'Requisition',
                'sub-title' => 'Requisition not found',
                'success' => false,
            ], 404);
        }

        if (!$requisition->canApprove()) {
            return response()->json([
                'title' => 'Requisition',
                'sub-title' => 'Requisition cannot be approved',
                'success' => false,
            ], 400);
        }

        // Process approved quantities
        $approvedQuantities = [];
        if ($request->filled('items')) {
            foreach ($request->items as $itemData) {
                $approvedQuantities[$itemData['id']] = $itemData['approved_quantity'];
            }
        }

        $requisition->approve($request->user()->id, $approvedQuantities);

        // Notify requester about approval
        if ($requisition->requester_id) {
            Notification::send(
                $requisition->requester_id,
                'Requisition Approved: ' . $requisition->requisition_number,
                'Your requisition ' . $requisition->requisition_number . ' has been approved.',
                'requisition',
                ['requisition_id' => $requisition->id, 'action' => 'approved'],
                $request->company->id
            );
        }

        // Notify IT Purchase (HO) if requisition is TO head_office
        if ($requisition->to_location_type === 'head_office') {
            $itPurchaseUsers = User::whereHas('position', fn($q) => $q->where('role_id', 4))
                ->whereHas('companies', fn($q) => $q->where('companies.id', $request->company->id))
                ->pluck('id')->toArray();

            if (!empty($itPurchaseUsers)) {
                Notification::sendToMany(
                    $itPurchaseUsers,
                    'New Requisition from ' . ucfirst(str_replace('_', ' ', $requisition->from_location_type)),
                    'Requisition ' . $requisition->requisition_number . ' approved and needs fulfillment.',
                    'requisition',
                    ['requisition_id' => $requisition->id, 'action' => 'needs_fulfillment'],
                    $request->company->id
                );
            }
        }

        return response()->json([
            'title' => 'Requisition',
            'sub-title' => 'Requisition approved',
            'success' => true,
            'data' => $requisition->fresh(['items.sku:id,code,name,unit']),
        ], 200);
    }

    /**
     * Reject a requisition
     */
    public function reject(Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $requisition = Requisition::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$requisition) {
            return response()->json([
                'title' => 'Requisition',
                'sub-title' => 'Requisition not found',
                'success' => false,
            ], 404);
        }

        if (!$requisition->canApprove()) {
            return response()->json([
                'title' => 'Requisition',
                'sub-title' => 'Requisition cannot be rejected',
                'success' => false,
            ], 400);
        }

        $requisition->reject($request->reason);

        // Notify requester about rejection
        if ($requisition->requester_id) {
            Notification::send(
                $requisition->requester_id,
                'Requisition Rejected: ' . $requisition->requisition_number,
                'Your requisition ' . $requisition->requisition_number . ' has been rejected. Reason: ' . $request->reason,
                'requisition',
                ['requisition_id' => $requisition->id, 'action' => 'rejected'],
                $request->company->id
            );
        }

        return response()->json([
            'title' => 'Requisition',
            'sub-title' => 'Requisition rejected',
            'success' => true,
            'data' => $requisition,
        ], 200);
    }

    /**
     * Fulfill requisition items (transfer inventory)
     * - Creates Challan for items that have stock
     * - Items without stock are skipped (IT Purchase must create PO manually)
     */
    public function fulfill(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:requisition_items,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'driver_id' => 'required|exists:users,id',
            'vehicle_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
        ], [
            'driver_id.required' => 'Please assign a driver before processing',
        ]);

        $requisition = Requisition::with('items.sku')
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$requisition) {
            return response()->json([
                'title' => 'Requisition',
                'sub-title' => 'Requisition not found',
                'success' => false,
            ], 404);
        }

        if (!in_array($requisition->status, [Requisition::STATUS_APPROVED, Requisition::STATUS_PARTIAL])) {
            return response()->json([
                'title' => 'Requisition',
                'sub-title' => 'Requisition must be approved to fulfill',
                'success' => false,
            ], 400);
        }

        // Track fulfilled items and items with insufficient stock
        $fulfilledItems = [];
        $insufficientStockItems = [];

        foreach ($request->items as $itemData) {
            $item = $requisition->items()->find($itemData['id']);
            if (!$item || $itemData['quantity'] <= 0)
                continue;

            $requestedQty = min($itemData['quantity'], $item->pending_quantity);
            if ($requestedQty <= 0)
                continue;

            // Check source inventory
            $sourceInventory = Inventory::where('sku_id', $item->sku_id)
                ->where('location_type', $requisition->from_location_type)
                ->where('location_id', $requisition->from_location_id ?? 0)
                ->first();

            $availableQty = $sourceInventory ? $sourceInventory->available_quantity : 0;

            if ($availableQty <= 0) {
                // No stock available - track for alert (no auto PO)
                $insufficientStockItems[] = [
                    'sku_name' => $item->sku->name ?? 'Unknown',
                    'sku_code' => $item->sku->code ?? '-',
                    'requested_quantity' => $requestedQty,
                    'available_quantity' => 0,
                    'shortage' => $requestedQty,
                ];
            } elseif ($availableQty < $requestedQty) {
                // Partial stock - fulfill what's available, track shortage
                $fulfillQty = $availableQty;
                $shortageQty = $requestedQty - $availableQty;

                // Record dispatch movement (from HO to destination) — visible to IT Purchase only.
                // Warehouse receipt will be logged separately on GRN completion.
                InventoryMovement::record([
                    'sku_id' => $item->sku_id,
                    'from_location_type' => $requisition->from_location_type,
                    'from_location_id' => $requisition->from_location_id,
                    'to_location_type' => $requisition->to_location_type,
                    'to_location_id' => $requisition->to_location_id,
                    'quantity' => $fulfillQty,
                    'movement_type' => InventoryMovement::TYPE_TRANSFER,
                    'reference_type' => 'requisition',
                    'reference_id' => $requisition->id,
                    'created_by' => $request->user()->id,
                    'company_id' => $request->company->id,
                ], false);  // Pass false to prevent auto-inventory update

                // Manually deduct from source (HO) only
                $sourceInventory->deduct($fulfillQty);

                $item->fulfilled_quantity += $fulfillQty;
                $item->save();

                $fulfilledItems[] = [
                    'requisition_item' => $item,
                    'quantity' => $fulfillQty,
                ];

                // Track shortage for alert
                $insufficientStockItems[] = [
                    'sku_name' => $item->sku->name ?? 'Unknown',
                    'sku_code' => $item->sku->code ?? '-',
                    'requested_quantity' => $requestedQty,
                    'available_quantity' => $availableQty,
                    'fulfilled_quantity' => $fulfillQty,
                    'shortage' => $shortageQty,
                ];
            } else {
                // Full stock available - fulfill completely
                // Record dispatch movement (from HO to destination) — visible to IT Purchase only.
                // Warehouse receipt will be logged separately on GRN completion.
                InventoryMovement::record([
                    'sku_id' => $item->sku_id,
                    'from_location_type' => $requisition->from_location_type,
                    'from_location_id' => $requisition->from_location_id,
                    'to_location_type' => $requisition->to_location_type,
                    'to_location_id' => $requisition->to_location_id,
                    'quantity' => $requestedQty,
                    'movement_type' => InventoryMovement::TYPE_TRANSFER,
                    'reference_type' => 'requisition',
                    'reference_id' => $requisition->id,
                    'created_by' => $request->user()->id,
                    'company_id' => $request->company->id,
                ], false);  // Pass false to prevent auto-inventory update

                // Manually deduct from source (HO) only
                $sourceInventory->deduct($requestedQty);

                $item->fulfilled_quantity += $requestedQty;
                $item->save();

                $fulfilledItems[] = [
                    'requisition_item' => $item,
                    'quantity' => $requestedQty,
                ];
            }
        }

        // Create Challan for fulfilled items only
        $challan = null;
        if (!empty($fulfilledItems)) {
            $challan = $this->createChallanForRequisition($requisition, $fulfilledItems, $request);
        }

        // Save driver assignment and fulfillment notes
        $updateData = [];
        if ($request->filled('driver_id')) {
            $updateData['driver_id'] = $request->driver_id;
        }
        if ($request->filled('vehicle_number')) {
            $updateData['vehicle_number'] = $request->vehicle_number;
        }
        if ($request->filled('notes')) {
            $updateData['fulfillment_notes'] = $request->notes;
        }

        // Create journey plan if driver is assigned and items were fulfilled
        $journeyPlan = null;
        if ($request->filled('driver_id') && !empty($fulfilledItems)) {
            $journeyPlan = $this->createDeliveryJourneyPlan($requisition, $request);
            if ($journeyPlan) {
                $updateData['journey_plan_id'] = $journeyPlan->id;
                // Update challan with journey plan
                if ($challan) {
                    $challan->update(['journey_plan_id' => $journeyPlan->id]);
                }
            }
        }

        if (!empty($updateData)) {
            $requisition->update($updateData);
        }

        // Refresh items relationship before updating status
        $requisition->load('items');
        $requisition->updateFulfillmentStatus();

        // Prepare response with detailed outcome
        $response = [
            'title' => 'Requisition',
            'sub-title' => 'Requisition processed',
            'success' => true,
            'data' => $requisition->fresh(['items.sku:id,code,name,unit', 'driver:id,first_name,last_name']),
            'fulfilled_count' => count($fulfilledItems),
            'insufficient_stock_count' => count($insufficientStockItems),
        ];

        if ($challan) {
            $response['challan'] = [
                'id' => $challan->id,
                'challan_number' => $challan->challan_number,
                'items_count' => count($fulfilledItems),
            ];
        }

        // Include insufficient stock items for alert display
        if (!empty($insufficientStockItems)) {
            $response['insufficient_stock_items'] = $insufficientStockItems;
            $response['insufficient_stock_message'] = 'Some items have insufficient stock at HO. Please create a Purchase Order manually if needed.';
        }

        // Notify driver about challan delivery
        if ($challan && $request->driver_id) {
            Notification::send(
                $request->driver_id,
                'New Delivery: ' . $challan->challan_number,
                'Challan ' . $challan->challan_number . ' assigned to you for delivery.',
                'challan',
                ['challan_id' => $challan->id, 'requisition_id' => $requisition->id, 'action' => 'assigned'],
                $request->company->id
            );
        }

        // Notify destination location users about incoming stock
        if ($challan) {
            $destRoleMap = ['warehouse' => 5, 'company_godown' => 6, 'franchise' => 7];
            $destRoleId = $destRoleMap[$requisition->to_location_type] ?? null;
            if ($destRoleId) {
                $destUsers = User::whereHas('position', fn($q) => $q->where('role_id', $destRoleId))
                    ->whereHas('companies', fn($q) => $q->where('companies.id', $request->company->id))
                    ->pluck('id')->toArray();

                if (!empty($destUsers)) {
                    Notification::sendToMany(
                        $destUsers,
                        'Incoming Stock: ' . $challan->challan_number,
                        'Challan ' . $challan->challan_number . ' dispatched to your location against Requisition ' . $requisition->requisition_number,
                        'challan',
                        ['challan_id' => $challan->id, 'requisition_id' => $requisition->id, 'action' => 'incoming'],
                        $request->company->id
                    );
                }
            }
        }

        return response()->json($response, 200);
    }

    /**
     * Create a Challan for fulfilled items
     */
    private function createChallanForRequisition(Requisition $requisition, array $fulfilledItems, Request $request)
    {
        $challan = Challan::create([
            'challan_number' => Challan::generateChallanNumber($request->company->id),
            'requisition_id' => $requisition->id,
            'from_location_type' => $requisition->from_location_type,
            'from_location_id' => $requisition->from_location_id ?? 0,
            'to_location_type' => $requisition->to_location_type,
            'to_location_id' => $requisition->to_location_id,
            'driver_id' => $request->driver_id,
            'vehicle_number' => $request->vehicle_number,
            'status' => Challan::STATUS_GENERATED,
            'notes' => $request->notes ?? 'Generated from Requisition #' . $requisition->requisition_number,
            'created_by' => $request->user()->id,
            'company_id' => $request->company->id,
        ]);

        // Add items to Challan
        foreach ($fulfilledItems as $itemData) {
            $reqItem = $itemData['requisition_item'];
            ChallanItem::create([
                'challan_id' => $challan->id,
                'sku_id' => $reqItem->sku_id,
                'requisition_item_id' => $reqItem->id,
                'quantity' => $itemData['quantity'],
                'received_quantity' => 0,
            ]);
        }

        return $challan;
    }

    /**
     * Create a journey plan for delivering requisition items
     */
    private function createDeliveryJourneyPlan(Requisition $requisition, Request $request)
    {
        // Get destination location details
        $destination = $requisition->getToLocation();
        if (!$destination) {
            return null;
        }

        // Reuse existing today's journey plan for this driver if it is still active
        $journeyPlan = JourneyPlan::where('driver_id', $request->driver_id)
            ->where('company_id', $request->company->id)
            ->whereDate('date', now()->toDateString())
            ->where('is_deleted', false)
            ->whereIn('status', [JourneyPlan::STATUS_PLANNED, JourneyPlan::STATUS_STARTED])
            ->first();

        if (!$journeyPlan) {
            $journeyPlan = JourneyPlan::create([
                'driver_id' => $request->driver_id,
                'date' => now()->toDateString(),
                'status' => JourneyPlan::STATUS_PLANNED,
                'notes' => 'Delivery for Requisition #' . $requisition->requisition_number,
                'company_id' => $request->company->id,
            ]);
        }

        // Append stop after any existing stops
        $nextSequence = ($journeyPlan->stops()->max('sequence') ?? 0) + 1;

        JourneyStop::create([
            'journey_plan_id' => $journeyPlan->id,
            'stop_type' => JourneyStop::TYPE_DELIVERY,
            'reference_type' => $requisition->to_location_type,
            'reference_id' => $requisition->to_location_id,
            'sequence' => $nextSequence,
            'name' => $destination->name ?? 'Delivery Point',
            'address' => $destination->address ?? ($destination->location->address ?? null),
            'latitude' => $destination->latitude ?? ($destination->location->latitude ?? null),
            'longitude' => $destination->longitude ?? ($destination->location->longitude ?? null),
            'status' => JourneyStop::STATUS_PENDING,
            'notes' => 'Deliver items for Requisition #' . $requisition->requisition_number,
        ]);

        return $journeyPlan;
    }

    /**
     * Get my requisitions
     */
    public function myRequisitions(Request $request)
    {
        $query = Requisition::with([
            'items.sku:id,code,name,unit',
            'requester:id,first_name,last_name',
            'approver:id,first_name,last_name',
        ])
            ->withCount('items')
            ->where('company_id', $request->company->id)
            ->where('requested_by', $request->user()->id)
            ->where('is_deleted', false)
            ->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $count = $query->count();

        if ($request->filled('paged') && $request->filled('itemsPerPage')) {
            $items = $query->paginate($request->itemsPerPage)->items();
        } else {
            $items = $query->limit(50)->get();
        }

        return response()->json([
            'title' => 'My Requisitions',
            'sub-title' => 'Requisitions fetched',
            'success' => true,
            'count' => $count,
            'data' => $items,
        ], 200);
    }

    /**
     * Get pending approvals (for approvers)
     */
    public function pendingApprovals(Request $request)
    {
        $query = Requisition::with([
            'items.sku:id,code,name,unit',
            'requester:id,first_name,last_name',
        ])
            ->where('company_id', $request->company->id)
            ->where('status', Requisition::STATUS_PENDING)
            ->where('is_deleted', false);

        // Filter by destination location if user is managing specific location
        if ($request->filled('from_location_type')) {
            $query->where('from_location_type', $request->from_location_type);
        }
        if ($request->filled('from_location_id')) {
            $query->where('from_location_id', $request->from_location_id);
        }

        $items = $query->orderBy('request_date', 'asc')->get();

        // Add location info
        $items->each(function ($req) {
            $req->from_location = $req->getFromLocation();
            $req->to_location = $req->getToLocation();
        });

        return response()->json([
            'title' => 'Pending Approvals',
            'sub-title' => 'Pending requisitions fetched',
            'success' => true,
            'count' => $items->count(),
            'data' => $items,
        ], 200);
    }

    /**
     * Get approved requisitions pending fulfillment
     */
    public function pendingFulfillment(Request $request)
    {
        $query = Requisition::with([
            'items.sku:id,code,name,unit',
            'requester:id,first_name,last_name',
        ])
            ->where('company_id', $request->company->id)
            ->whereIn('status', [Requisition::STATUS_APPROVED, Requisition::STATUS_PARTIAL])
            ->where('is_deleted', false);

        // Filter by source location
        if ($request->filled('from_location_type')) {
            $query->where('from_location_type', $request->from_location_type);
        }
        if ($request->filled('from_location_id')) {
            $query->where('from_location_id', $request->from_location_id);
        }

        $items = $query->orderBy('required_date', 'asc')->get();

        // Add location info
        $items->each(function ($req) {
            $req->from_location = $req->getFromLocation();
            $req->to_location = $req->getToLocation();
        });

        return response()->json([
            'title' => 'Pending Fulfillment',
            'sub-title' => 'Requisitions pending fulfillment',
            'success' => true,
            'count' => $items->count(),
            'data' => $items,
        ], 200);
    }

    /**
     * Cancel a requisition
     */
    public function cancel(Request $request)
    {
        $requisition = Requisition::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$requisition) {
            return response()->json([
                'title' => 'Requisition',
                'sub-title' => 'Requisition not found',
                'success' => false,
            ], 404);
        }

        if (in_array($requisition->status, [Requisition::STATUS_FULFILLED])) {
            return response()->json([
                'title' => 'Requisition',
                'sub-title' => 'Cannot cancel fulfilled requisitions',
                'success' => false,
            ], 400);
        }

        $requisition->update(['status' => Requisition::STATUS_CANCELLED]);

        return response()->json([
            'title' => 'Requisition',
            'sub-title' => 'Requisition cancelled',
            'success' => true,
            'data' => $requisition,
        ], 200);
    }
}
