<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Sku;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InventoriesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'adjust']);
    }

    /**
     * Get inventory list
     */
    public function index(Request $request)
    {
        try {
            $query = Inventory::with(['sku:id,code,name,unit,mrp,selling_price,out_of_stock_threshold']);
            $query = Utility::prepareSearchQuery($query, $request, new Inventory());

            // IT Purchase position can only view Head Office inventory
            $user = $request->user();
            if ($user && $user->position && $user->position->name === 'IT Purchase') {
                $query->where('location_type', 'head_office');
            }

            $inventories = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title' => 'Inventory',
                'sub-title' => 'Inventory fetched successfully',
                'success' => true,
                'data' => $inventories,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get inventory at a specific location
     */
    public function atLocation(Request $request, $locationType, $locationId)
    {
        $query = Inventory::with(['sku:id,code,name,unit,mrp,selling_price'])
            ->where('company_id', $request->company->id)
            ->where('location_type', $locationType)
            ->where('location_id', $locationId)
            ->where('quantity', '>', 0);

        // Search by SKU
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('sku', function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        $query->orderBy('quantity', 'desc');

        $items = $query->get();

        return response()->json([
            'title' => 'Location Inventory',
            'sub-title' => 'Inventory fetched',
            'success' => true,
            'count' => $items->count(),
            'data' => $items,
        ], 200);
    }

    /**
     * Get single inventory record
     */
    public function show(Request $request)
    {
        $inventory = Inventory::with(['sku'])
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$inventory) {
            return response()->json([
                'title' => 'Inventory',
                'sub-title' => 'Inventory not found',
                'success' => false,
            ], 404);
        }

        return response()->json([
            'title' => 'Inventory',
            'sub-title' => 'Inventory fetched successfully',
            'success' => true,
            'data' => $inventory,
        ], 200);
    }

    /**
     * Update inventory settings (min/max levels)
     */
    public function update(Request $request)
    {
        $request->validate([
            'min_quantity' => 'nullable|numeric|min:0',
            'max_quantity' => 'nullable|numeric|min:0',
        ]);

        $inventory = Inventory::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$inventory) {
            return response()->json([
                'title' => 'Inventory',
                'sub-title' => 'Inventory not found',
                'success' => false,
            ], 404);
        }

        $inventory->update($request->only(['min_quantity', 'max_quantity']));

        return response()->json([
            'title' => 'Inventory',
            'sub-title' => 'Inventory updated',
            'success' => true,
            'data' => $inventory,
        ], 200);
    }

    /**
     * Adjust inventory (manual adjustment)
     */
    public function adjust(Request $request)
    {
        $request->validate([
            'adjustment' => 'required|numeric',
            'reason' => 'required|string|max:500',
        ]);

        $inventory = Inventory::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$inventory) {
            return response()->json([
                'title' => 'Inventory',
                'sub-title' => 'Inventory not found',
                'success' => false,
            ], 404);
        }

        $adjustment = $request->adjustment;
        $newQuantity = $inventory->quantity + $adjustment;

        if ($newQuantity < 0) {
            return response()->json([
                'title' => 'Inventory',
                'sub-title' => 'Adjustment would result in negative quantity',
                'success' => false,
            ], 400);
        }

        // Record movement
        InventoryMovement::create([
            'sku_id' => $inventory->sku_id,
            'from_location_type' => $adjustment < 0 ? $inventory->location_type : null,
            'from_location_id' => $adjustment < 0 ? $inventory->location_id : null,
            'to_location_type' => $adjustment > 0 ? $inventory->location_type : null,
            'to_location_id' => $adjustment > 0 ? $inventory->location_id : null,
            'quantity' => abs($adjustment),
            'movement_type' => InventoryMovement::TYPE_ADJUSTMENT,
            'notes' => $request->reason,
            'created_by' => $request->user()->id,
            'company_id' => $request->company->id,
        ]);

        // Update inventory
        $inventory->quantity = $newQuantity;
        $inventory->save();

        return response()->json([
            'title' => 'Inventory',
            'sub-title' => 'Inventory adjusted',
            'success' => true,
            'data' => $inventory,
        ], 200);
    }

    /**
     * Get low stock items
     */
    public function lowStock(Request $request)
    {
        $query = Inventory::with(['sku:id,code,name,unit'])
            ->where('company_id', $request->company->id)
            ->lowStock();

        // Filter by location
        if ($request->filled('location_type')) {
            $query->where('location_type', $request->location_type);
        }
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        $query->orderByRaw('quantity - min_quantity');
        $items = Utility::getSearchRequestQueryResults($request, $query);

        return response()->json([
            'title' => 'Low Stock',
            'sub-title' => 'Low stock items fetched',
            'success' => true,
            'data' => $items,
        ], 200);
    }

    /**
     * Get out of stock items
     */
    public function outOfStock(Request $request)
    {
        $query = Inventory::with(['sku:id,code,name,unit'])
            ->where('company_id', $request->company->id)
            ->outOfStock();

        // Filter by location
        if ($request->filled('location_type')) {
            $query->where('location_type', $request->location_type);
        }
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        $query->orderBy('id', 'desc');
        $items = Utility::getSearchRequestQueryResults($request, $query);

        return response()->json([
            'title' => 'Out of Stock',
            'sub-title' => 'Out of stock items fetched',
            'success' => true,
            'data' => $items,
        ], 200);
    }

    /**
     * Get inventory movements
     */
    public function movements(Request $request)
    {
        $query = InventoryMovement::with([
            'sku:id,code,name,unit',
            'creator:id,first_name,last_name',
        ])
            ->where('company_id', $request->company->id);

        // IT Purchase position can only view Head Office inventory movements
        $user = $request->user();
        if ($user && $user->position && $user->position->name === 'IT Purchase') {
            $query->where(function ($q) {
                $q->where('from_location_type', 'head_office')
                    ->orWhere('to_location_type', 'head_office');
            });
        }

        // For warehouse: show deduction movements (from_location_type=warehouse) but exclude
        // incoming requisition dispatch transfers (where warehouse is the destination).
        // For CG/franchise: exclude requisition dispatch movements; actual receipt logged on GRN completion.
        $locationType = $request->input('search.location_type');
        if ($locationType === 'warehouse') {
            $query->where(function ($q) use ($locationType) {
                $q->where('reference_type', '!=', 'requisition')
                    ->orWhereNull('reference_type')
                    ->orWhere('from_location_type', $locationType); // show outbound deductions
            });
        } elseif (in_array($locationType, ['company_godown', 'franchise'])) {
            $query->where(function ($q) {
                $q->where('reference_type', '!=', 'requisition')
                    ->orWhereNull('reference_type');
            });
        }

        // Filter by SKU
        if ($request->filled('sku_id')) {
            $query->where('sku_id', $request->sku_id);
        }

        // Filter by location
        $locationType = $request->input('search.location_type');
        $locationId = $request->input('search.location_id');

        if ($locationType && $locationId) {
            // Both type and ID provided - filter by exact location
            $query->atLocation($locationType, $locationId);
        } elseif ($locationType) {
            // Only type provided - filter by location type (from OR to)
            $query->where(function ($q) use ($locationType) {
                $q->where('from_location_type', $locationType)
                    ->orWhere('to_location_type', $locationType);
            });
        }

        // Filter by movement type
        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        // Filter by date range
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [$request->from_date . ' 00:00:00', $request->to_date . ' 23:59:59']);
        }

        $query->orderBy('created_at', 'desc');

        $count = $query->count();

        if ($request->filled('page') && $request->filled('rowsPerPage')) {
            $items = $query->paginate($request->rowsPerPage)->items();
        } else {
            $items = $query->limit(100)->get();
        }

        return response()->json([
            'title' => 'Inventory Movements',
            'sub-title' => 'Movements fetched',
            'success' => true,
            'count' => $count,
            'data' => $items,
        ], 200);
    }

    /**
     * Get SKU stock across all locations
     */
    public function skuStock(Request $request, $skuId)
    {
        $sku = Sku::where('id', $skuId)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$sku) {
            return response()->json([
                'title' => 'SKU Stock',
                'sub-title' => 'SKU not found',
                'success' => false,
            ], 404);
        }

        $inventory = Inventory::where('company_id', $request->company->id)
            ->where('sku_id', $skuId)
            ->get()
            ->map(function ($inv) {
                $inv->location = $inv->getLocation();
                return $inv;
            });

        $totalStock = $inventory->sum('quantity');
        $totalReserved = $inventory->sum('reserved_quantity');

        return response()->json([
            'title' => 'SKU Stock',
            'sub-title' => 'Stock fetched',
            'success' => true,
            'data' => [
                'sku' => $sku,
                'total_stock' => $totalStock,
                'total_reserved' => $totalReserved,
                'locations' => $inventory,
            ],
        ], 200);
    }

    /**
     * Get inventory summary
     */
    public function summary(Request $request)
    {
        $query = Inventory::where('company_id', $request->company->id);

        // Filter by location
        if ($request->filled('location_type')) {
            $query->where('location_type', $request->location_type);
        }
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        $totalSkus = $query->distinct('sku_id')->count('sku_id');
        $totalQuantity = (clone $query)->sum('quantity');
        $lowStockCount = (clone $query)->lowStock()->count();
        $outOfStockCount = (clone $query)->outOfStock()->count();

        return response()->json([
            'title' => 'Inventory Summary',
            'sub-title' => 'Summary fetched',
            'success' => true,
            'data' => [
                'total_skus' => $totalSkus,
                'total_quantity' => $totalQuantity,
                'low_stock_count' => $lowStockCount,
                'out_of_stock_count' => $outOfStockCount,
            ],
        ], 200);
    }
}
