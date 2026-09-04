<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Role;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RolesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'allowed_urls']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'clear', 'restore']);
    }

    public function index(Request $request)
    {
        try {
            $query = Role::query();
            $query = Utility::prepareSearchQuery($query, $request, new Role());
            $query->orderBy('id', 'desc');
            $roles = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title'     => 'Role',
                'sub-title' => 'Role listing Fetched Successfully',
                'success'   => true,
                'data'      => $roles,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|max:100',
        ]);

        $role = Role::create($request->all());

        return response()->json([
            'title'     => 'Role',
            'sub-title' => 'Role Data Stored Successfully',
            'success'   => true,
            'data'      => $role,
        ], 200);
    }

    public function show(Request $request)
    {
        $decryptedID = $request->id;
        $role = Role::where('id', $decryptedID)->with(['role_permissions', 'permissions'])->first();

        return response()->json([
            'title'     => 'Role',
            'sub-title' => 'Role Data Fetched Successfully',
            'success'   => true,
            'data'      => $role,
        ], 200);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name'    => 'required|max:100',
        ]);

        $decryptedID = $request->id;
        $role = Role::where('id', $decryptedID)->first();
        $data = $request->all();
        $role->update($data);

        return response()->json([
            'title'     => 'Role',
            'sub-title' => 'Role Data Updated Successfully',
            'success'   => true,
            'data'      => $role,
        ], 200);
    }

    public function clear(Request $request)
    {
        $id = $request->id;
        $role = Role::find($id)->update(['is_deleted' => true]);

        return response()->json([
            'title'     => 'Role',
            'sub-title' => 'Role Data Deleted Successfully',
            'success'   => true,
            'data'      => $role,
        ], 200);
    }

    public function restore(Request $request)
    {
        $id = $request->id;
        $role = Role::find($id)->update(['is_deleted' => false]);

        return response()->json([
            'title'     => 'Role',
            'sub-title' => 'Role Data Restored Successfully',
            'success'   => true,
            'data'      => $role,
        ], 200);
    }
}
