<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\CompanyGodown;
use Illuminate\Http\Request;

class CompanyGodownsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'clear', 'restore']);
    }

    public function index(Request $request)
    {
        $query = $request->boolean('show_deleted')
            ? $request->company->deletedCompanyGodowns()
            : $request->company->allCompanyGodowns();

        $query = Utility::prepareSearchQuery($query, $request, new CompanyGodown());

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
                  ->orWhere('contact_person', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
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
            'title'     => 'Company Godown',
            'sub-title' => 'Company Godown listing Fetched Successfully',
            'success'   => true,
            'count'     => $count,
            'data'      => $items,
        ], 200);
    }

    public function store(Request $request)
    {
        $attributes = $request->validate([
            'name'           => 'required|string|max:100',
            'code'           => 'nullable|string|max:20',
            'warehouse_id'   => 'nullable|exists:warehouses,id',
            'location_id'    => 'nullable|exists:locations,id',
            'contact_person' => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:20',
            'capacity'       => 'nullable|integer|min:0',
            'is_active'      => 'nullable|boolean',
        ]);

        $godown = new CompanyGodown($attributes);
        $request->company->companyGodowns()->save($godown);

        return response()->json([
            'title'     => 'Company Godown',
            'sub-title' => 'Company Godown Created Successfully',
            'success'   => true,
            'data'      => $godown->load(['warehouse', 'location']),
        ], 200);
    }

    public function show(Request $request)
    {
        $decryptedID = $request->id;
        $godown = CompanyGodown::where('id', $decryptedID)
            ->with(['warehouse', 'location'])
            ->first();

        if (!$godown) {
            return response()->json([
                'title'     => 'Company Godown',
                'sub-title' => 'Company Godown Not Found',
                'success'   => false,
                'data'      => null,
            ], 404);
        }

        return response()->json([
            'title'     => 'Company Godown',
            'sub-title' => 'Company Godown Data Fetched Successfully',
            'success'   => true,
            'data'      => $godown,
        ], 200);
    }

    public function update(Request $request)
    {
        $decryptedID = $request->id;
        $godown = CompanyGodown::where('id', $decryptedID)->first();

        if (!$godown) {
            return response()->json([
                'title'     => 'Company Godown',
                'sub-title' => 'Company Godown Not Found',
                'success'   => false,
                'data'      => null,
            ], 404);
        }

        $attributes = $request->validate([
            'name'           => 'required|string|max:100',
            'code'           => 'nullable|string|max:20',
            'warehouse_id'   => 'nullable|exists:warehouses,id',
            'location_id'    => 'nullable|exists:locations,id',
            'contact_person' => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:20',
            'capacity'       => 'nullable|integer|min:0',
            'is_active'      => 'nullable|boolean',
        ]);

        $godown->update($attributes);

        return response()->json([
            'title'     => 'Company Godown',
            'sub-title' => 'Company Godown Updated Successfully',
            'success'   => true,
            'data'      => $godown->load(['warehouse', 'location']),
        ], 200);
    }

    public function clear(Request $request)
    {
        $id = $request->id;
        $godown = CompanyGodown::find($id);

        if (!$godown) {
            return response()->json([
                'title'     => 'Company Godown',
                'sub-title' => 'Company Godown Not Found',
                'success'   => false,
            ], 404);
        }

        $godown->update(['is_deleted' => true]);

        return response()->json([
            'title'     => 'Company Godown',
            'sub-title' => 'Company Godown Deleted Successfully',
            'success'   => true,
            'data'      => $godown,
        ], 200);
    }

    public function restore(Request $request)
    {
        $id = $request->id;
        $godown = CompanyGodown::find($id);

        if (!$godown) {
            return response()->json([
                'title'     => 'Company Godown',
                'sub-title' => 'Company Godown Not Found',
                'success'   => false,
            ], 404);
        }

        $godown->update(['is_deleted' => false]);

        return response()->json([
            'title'     => 'Company Godown',
            'sub-title' => 'Company Godown Restored Successfully',
            'success'   => true,
            'data'      => $godown,
        ], 200);
    }
}
