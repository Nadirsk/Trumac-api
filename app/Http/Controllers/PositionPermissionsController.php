<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\PositionPermission;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PositionPermissionsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = PositionPermission::query();
            $query = Utility::prepareSearchQuery($query, $request, new PositionPermission());
            $positionPermissions = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title'     => 'Position Permission',
                'sub-title' => 'Position Permission listing Fetched Successfully',
                'success'   => true,
                'data'      => $positionPermissions,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'      => 'required',
            'doc_type_id'  => 'required|max:100',
        ]);

        $position_permission = PositionPermission::create($request->all());

        return response()->json([
            'title'     => 'Position Permission',
            'sub-title' => 'Position Permission Data Stored Successfully',
            'success'   => true,
            'data'      => $position_permission,
        ], 200);
    }

    public function show(Request $request)
    {
        $decryptedID = $request->id;
        $position_permission = PositionPermission::where('id', $decryptedID)->first();

        return response()->json([
            'title'     => 'Position Permission',
            'sub-title' => 'Position Permission Data Fetched Successfully',
            'success'   => true,
            'data'      => $position_permission,
        ], 200);
    }

    public function update(Request $request)
    {
        $request->validate([
            'user_id'      => 'required',
            'doc_type_id'  => 'required|max:100',
        ]);

        $decryptedID = $request->id;
        $position_permission = PositionPermission::where('id', $decryptedID)->first();
        $data = $request->all();
        $position_permission->update($data);

        return response()->json([
            'title'     => 'Position Permission',
            'sub-title' => 'Position Permission Data Updated Successfully',
            'success'   => true,
            'data'      => $position_permission,
        ], 200);
    }

    public function clear(Request $request)
    {
        $id = $request->id;
        $position_permission = PositionPermission::find($id)->update(['is_deleted' => true]);

        return response()->json([
            'title'     => 'Position Permission',
            'sub-title' => 'Position Permission Data Deleted Successfully',
            'success'   => true,
            'data'      => $position_permission,
        ], 200);
    }

    public function restore(Request $request)
    {
        $id = $request->id;
        $position_permission = PositionPermission::find($id)->update(['is_deleted' => false]);

        return response()->json([
            'title'     => 'Position Permission',
            'sub-title' => 'Position Permission Data Restored Successfully',
            'success'   => true,
            'data'      => $position_permission,
        ], 200);
    }
}
