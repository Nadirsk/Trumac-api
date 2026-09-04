<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'clear', 'restore']);
    }

    public function index(Request $request)
    {
        $query = $request->boolean('show_deleted')
            ? $request->company->deletedVendors()
            : $request->company->allVendors();

        $query = Utility::prepareSearchQuery($query, $request, new Vendor());

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->filled('search_keyword')) {
            $search = $request->search_keyword;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('contact_person', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('gst_no', 'LIKE', "%{$search}%")
                  ->orWhere('pan_no', 'LIKE', "%{$search}%");
            });
        }

        $query->orderBy('id', 'desc');
        $count = $query->count();

        if ($request->filled('page') && $request->filled('rowsPerPage')) {
            $items = $query->paginate($request->rowsPerPage)->items();
        } else {
            $items = $query->get();
        }

        return response()->json([
            'title'     => 'Vendor',
            'sub-title' => 'Vendor listing Fetched Successfully',
            'success'   => true,
            'count'     => $count,
            'data'      => $items,
        ], 200);
    }

    public function store(Request $request)
    {
        $attributes = $request->validate([
            'name'           => 'required|string|max:100',
            'contact_person' => 'required|string|max:100',
            'phone'          => 'required|string|max:20',
            'email'          => 'required|email|max:100',
            'address'        => 'required|string',
            'city'           => 'required|string|max:100',
            'state'          => 'required|string|max:100',
            'pincode'        => 'required|string|max:10',
            'gst_no'         => 'required|string|max:20',
            'pan_no'         => 'required|string|max:20',
            'bank_name'      => 'required|string|max:100',
            'account_no'     => 'required|string|max:30',
            'ifsc'           => 'required|string|max:15',
            'branch_name'    => 'required|string|max:100',
            'is_active'      => 'nullable|boolean',
        ]);

        $vendor = new Vendor($attributes);
        $request->company->vendors()->save($vendor);

        return response()->json([
            'title'     => 'Vendor',
            'sub-title' => 'Vendor Created Successfully',
            'success'   => true,
            'data'      => $vendor,
        ], 200);
    }

    public function show(Request $request)
    {
        $decryptedID = $request->id;
        $vendor = Vendor::where('id', $decryptedID)->first();

        if (!$vendor) {
            return response()->json([
                'title'     => 'Vendor',
                'sub-title' => 'Vendor Not Found',
                'success'   => false,
                'data'      => null,
            ], 404);
        }

        return response()->json([
            'title'     => 'Vendor',
            'sub-title' => 'Vendor Data Fetched Successfully',
            'success'   => true,
            'data'      => $vendor,
        ], 200);
    }

    public function update(Request $request)
    {
        $decryptedID = $request->id;
        $vendor = Vendor::where('id', $decryptedID)->first();

        if (!$vendor) {
            return response()->json([
                'title'     => 'Vendor',
                'sub-title' => 'Vendor Not Found',
                'success'   => false,
                'data'      => null,
            ], 404);
        }

        $attributes = $request->validate([
            'name'           => 'required|string|max:100',
            'contact_person' => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:100',
            'address'        => 'nullable|string',
            'city'           => 'nullable|string|max:100',
            'state'          => 'nullable|string|max:100',
            'pincode'        => 'nullable|string|max:10',
            'gst_no'         => 'nullable|string|max:20',
            'pan_no'         => 'nullable|string|max:20',
            'bank_name'      => 'nullable|string|max:100',
            'account_no'     => 'nullable|string|max:30',
            'ifsc'           => 'nullable|string|max:15',
            'branch_name'    => 'nullable|string|max:100',
            'is_active'      => 'nullable|boolean',
        ]);

        $vendor->update($attributes);

        return response()->json([
            'title'     => 'Vendor',
            'sub-title' => 'Vendor Updated Successfully',
            'success'   => true,
            'data'      => $vendor,
        ], 200);
    }

    public function clear(Request $request)
    {
        $id = $request->id;
        $vendor = Vendor::find($id);

        if (!$vendor) {
            return response()->json([
                'title'     => 'Vendor',
                'sub-title' => 'Vendor Not Found',
                'success'   => false,
            ], 404);
        }

        $vendor->update(['is_deleted' => true]);

        return response()->json([
            'title'     => 'Vendor',
            'sub-title' => 'Vendor Deleted Successfully',
            'success'   => true,
            'data'      => $vendor,
        ], 200);
    }

    public function restore(Request $request)
    {
        $id = $request->id;
        $vendor = Vendor::find($id);

        if (!$vendor) {
            return response()->json([
                'title'     => 'Vendor',
                'sub-title' => 'Vendor Not Found',
                'success'   => false,
            ], 404);
        }

        $vendor->update(['is_deleted' => false]);

        return response()->json([
            'title'     => 'Vendor',
            'sub-title' => 'Vendor Restored Successfully',
            'success'   => true,
            'data'      => $vendor,
        ], 200);
    }
}
