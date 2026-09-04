<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehousesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'clear', 'restore']);
    }

    /**
     * @OA\Get(
     *     path="/api/warehouses",
     *     tags={"Warehouse"},
     *     summary="Get all Warehouses",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $query = $request->boolean('show_deleted')
            ? $request->company->deletedWarehouses()
            : $request->company->allWarehouses();

        $query = Utility::prepareSearchQuery($query, $request, new Warehouse());

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->filled('search_keyword')) {
            $search = $request->search_keyword;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%")
                  ->orWhere('contact_person', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $query->with('location');
        $query->orderBy('id', 'desc');
        $count = $query->count();

        if ($request->filled('page') && $request->filled('rowsPerPage')) {
            $items = $query->paginate($request->rowsPerPage)->items();
        } else {
            $items = $query->get();
        }

        return response()->json([
            'title'     => 'Warehouse',
            'sub-title' => 'Warehouse listing Fetched Successfully',
            'success'   => true,
            'count'     => $count,
            'data'      => $items,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/warehouses",
     *     tags={"Warehouse"},
     *     summary="Create a new Warehouse",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Unprocessable Entity")
     * )
     */
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'name'           => 'required|string|max:100',
            'code'           => 'nullable|string|max:20',
            'location_id'    => 'nullable|exists:locations,id',
            'contact_person' => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:100',
            'capacity'       => 'nullable|integer|min:0',
            'is_active'      => 'nullable|boolean',
        ]);

        $warehouse = new Warehouse($attributes);
        $request->company->warehouses()->save($warehouse);

        return response()->json([
            'title'     => 'Warehouse',
            'sub-title' => 'Warehouse Created Successfully',
            'success'   => true,
            'data'      => $warehouse->load('location'),
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/warehouses/{id}",
     *     tags={"Warehouse"},
     *     summary="Get a Warehouse by ID",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(Request $request)
    {
        $decryptedID = $request->id;
        $warehouse = Warehouse::where('id', $decryptedID)
            ->with('location')
            ->first();

        if (!$warehouse) {
            return response()->json([
                'title'     => 'Warehouse',
                'sub-title' => 'Warehouse Not Found',
                'success'   => false,
                'data'      => null,
            ], 404);
        }

        return response()->json([
            'title'     => 'Warehouse',
            'sub-title' => 'Warehouse Data Fetched Successfully',
            'success'   => true,
            'data'      => $warehouse,
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/warehouses/{id}",
     *     tags={"Warehouse"},
     *     summary="Update a Warehouse by ID",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Unprocessable Entity")
     * )
     */
    public function update(Request $request)
    {
        $decryptedID = $request->id;
        $warehouse = Warehouse::where('id', $decryptedID)->first();

        if (!$warehouse) {
            return response()->json([
                'title'     => 'Warehouse',
                'sub-title' => 'Warehouse Not Found',
                'success'   => false,
                'data'      => null,
            ], 404);
        }

        $attributes = $request->validate([
            'name'           => 'required|string|max:100',
            'code'           => 'nullable|string|max:20',
            'location_id'    => 'nullable|exists:locations,id',
            'contact_person' => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:100',
            'capacity'       => 'nullable|integer|min:0',
            'is_active'      => 'nullable|boolean',
        ]);

        $warehouse->update($attributes);

        return response()->json([
            'title'     => 'Warehouse',
            'sub-title' => 'Warehouse Updated Successfully',
            'success'   => true,
            'data'      => $warehouse->load('location'),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/warehouses/delete/{id}",
     *     tags={"Warehouse"},
     *     summary="Soft delete a Warehouse",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function clear(Request $request)
    {
        $id = $request->id;
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return response()->json([
                'title'     => 'Warehouse',
                'sub-title' => 'Warehouse Not Found',
                'success'   => false,
            ], 404);
        }

        $warehouse->update(['is_deleted' => true]);

        return response()->json([
            'title'     => 'Warehouse',
            'sub-title' => 'Warehouse Deleted Successfully',
            'success'   => true,
            'data'      => $warehouse,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/warehouses/restore/{id}",
     *     tags={"Warehouse"},
     *     summary="Restore a soft deleted Warehouse",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function restore(Request $request)
    {
        $id = $request->id;
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return response()->json([
                'title'     => 'Warehouse',
                'sub-title' => 'Warehouse Not Found',
                'success'   => false,
            ], 404);
        }

        $warehouse->update(['is_deleted' => false]);

        return response()->json([
            'title'     => 'Warehouse',
            'sub-title' => 'Warehouse Restored Successfully',
            'success'   => true,
            'data'      => $warehouse,
        ], 200);
    }
}
