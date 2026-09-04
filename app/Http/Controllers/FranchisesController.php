<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Franchise;
use Illuminate\Http\Request;

class FranchisesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'clear', 'restore']);
    }

    public function index(Request $request)
    {
        $query = $request->boolean('show_deleted')
            ? $request->company->deletedFranchises()
            : $request->company->allFranchises();

        $query = Utility::prepareSearchQuery($query, $request, new Franchise());

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('search_keyword')) {
            $search = $request->search_keyword;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%")
                  ->orWhere('owner_name', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('gst_no', 'LIKE', "%{$search}%");
            });
        }

        $query->with(['warehouse', 'location']);
        $query->withCount('retailers');
        $query->orderBy('id', 'desc');
        $count = $query->count();

        if ($request->filled('page') && $request->filled('rowsPerPage')) {
            $items = $query->paginate($request->rowsPerPage)->items();
        } else {
            $items = $query->get();
        }

        return response()->json([
            'title'     => 'Franchise',
            'sub-title' => 'Franchise listing Fetched Successfully',
            'success'   => true,
            'count'     => $count,
            'data'      => $items,
        ], 200);
    }

    public function store(Request $request)
    {
        $attributes = $request->validate([
            'name'         => 'required|string|max:100',
            'code'         => 'nullable|string|max:20',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'location_id'  => 'nullable|exists:locations,id',
            'owner_name'   => 'nullable|string|max:100',
            'phone'        => 'nullable|string|max:20',
            'email'        => 'nullable|email|max:100',
            'gst_no'       => 'nullable|string|max:20',
            'pan_no'       => 'nullable|string|max:20',
            'address'      => 'nullable|string',
            'is_active'    => 'nullable|boolean',
        ]);

        $franchise = new Franchise($attributes);
        $request->company->franchises()->save($franchise);

        return response()->json([
            'title'     => 'Franchise',
            'sub-title' => 'Franchise Created Successfully',
            'success'   => true,
            'data'      => $franchise->load(['warehouse', 'location']),
        ], 200);
    }

    public function show(Request $request)
    {
        $decryptedID = $request->id;
        $franchise = Franchise::where('id', $decryptedID)
            ->with(['warehouse', 'location'])
            ->withCount('retailers')
            ->first();

        if (!$franchise) {
            return response()->json([
                'title'     => 'Franchise',
                'sub-title' => 'Franchise Not Found',
                'success'   => false,
                'data'      => null,
            ], 404);
        }

        return response()->json([
            'title'     => 'Franchise',
            'sub-title' => 'Franchise Data Fetched Successfully',
            'success'   => true,
            'data'      => $franchise,
        ], 200);
    }

    public function update(Request $request)
    {
        $decryptedID = $request->id;
        $franchise = Franchise::where('id', $decryptedID)->first();

        if (!$franchise) {
            return response()->json([
                'title'     => 'Franchise',
                'sub-title' => 'Franchise Not Found',
                'success'   => false,
                'data'      => null,
            ], 404);
        }

        $attributes = $request->validate([
            'name'         => 'required|string|max:100',
            'code'         => 'nullable|string|max:20',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'location_id'  => 'nullable|exists:locations,id',
            'owner_name'   => 'nullable|string|max:100',
            'phone'        => 'nullable|string|max:20',
            'email'        => 'nullable|email|max:100',
            'gst_no'       => 'nullable|string|max:20',
            'pan_no'       => 'nullable|string|max:20',
            'address'      => 'nullable|string',
            'is_active'    => 'nullable|boolean',
        ]);

        $franchise->update($attributes);

        return response()->json([
            'title'     => 'Franchise',
            'sub-title' => 'Franchise Updated Successfully',
            'success'   => true,
            'data'      => $franchise->load(['warehouse', 'location']),
        ], 200);
    }

    public function clear(Request $request)
    {
        $id = $request->id;
        $franchise = Franchise::find($id);

        if (!$franchise) {
            return response()->json([
                'title'     => 'Franchise',
                'sub-title' => 'Franchise Not Found',
                'success'   => false,
            ], 404);
        }

        $franchise->update(['is_deleted' => true]);

        return response()->json([
            'title'     => 'Franchise',
            'sub-title' => 'Franchise Deleted Successfully',
            'success'   => true,
            'data'      => $franchise,
        ], 200);
    }

    public function restore(Request $request)
    {
        $id = $request->id;
        $franchise = Franchise::find($id);

        if (!$franchise) {
            return response()->json([
                'title'     => 'Franchise',
                'sub-title' => 'Franchise Not Found',
                'success'   => false,
            ], 404);
        }

        $franchise->update(['is_deleted' => false]);

        return response()->json([
            'title'     => 'Franchise',
            'sub-title' => 'Franchise Restored Successfully',
            'success'   => true,
            'data'      => $franchise,
        ], 200);
    }
}
