<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Position;
use App\Models\PositionPermission;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class PositionsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company', 'allowed_urls']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'clear', 'restore', 'destroy']);
    }

    /**
     * @OA\Get(
     *     path="/api/positions",
     *     tags={"Position"},
     *     summary="Get all Positions",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = Position::with(['role', 'position_permissions']);
            $query = Utility::prepareSearchQuery($query, $request, new Position());
            $query->orderBy('id', 'asc');
            $positions = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title'     => 'Position',
                'sub-title' => 'Position listing Fetched Successfully',
                'success'   => true,
                'data'      => $positions,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/positions",
     *     tags={"Position"},
     *     summary="Create a new Positions",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'role_id'  => 'required',
            'name'     => 'required|max:100',
        ]);

        if (empty($request->id)) {
            $position = new Position($request->all());
            $request->company->positions()->save($position);

            if (isset($request->position_permissions) && !empty($request->position_permissions)) {
                foreach ($request->position_permissions as $permission) {
                    $positionPermission = new PositionPermission($permission);
                    $position->position_permissions()->save($positionPermission);
                }
            }
        } else {
            $position = Position::findOrFail($request->id);
            $position->update($request->all());

            $positionPermissionIdResponseArray = isset($request->position_permissions)
                ? Arr::pluck($request->position_permissions, 'id')
                : [];

            $positionId = $position->id;
            $positionPermissionIdArray = PositionPermission::where('position_id', $positionId)->pluck('id')->toArray();

            $differencePositionPermissionIds = array_diff($positionPermissionIdArray, $positionPermissionIdResponseArray);

            if (!empty($differencePositionPermissionIds)) {
                PositionPermission::whereIn('id', $differencePositionPermissionIds)->delete();
            }

            // Update or create Position Permission
            if (isset($request->position_permissions) && !empty($request->position_permissions)) {
                foreach ($request->position_permissions as $permission) {
                    if (empty($permission['id'])) {
                        $position_permission = new PositionPermission($permission);
                        $position->position_permissions()->save($position_permission);
                    } else {
                        $position_permission = PositionPermission::findOrFail($permission['id']);
                        $position_permission->update($permission);
                    }
                }
            }
        }

        return response()->json([
            'title'     => 'Position',
            'sub-title' => 'Position Data Stored Successfully',
            'success'   => true,
            'data'      => $position,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/positions/{id}",
     *     tags={"Position"},
     *     summary="Get a Position by ID",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     )
     * )
     */
    /**
     * @OA\Get(
     *     path="/api/positions/{position}",
     *     tags={"Position"},
     *     summary="Get all {position}",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     )
     * )
     */
    public function show(Request $request)
    {
        $decryptedID = $request->id;
        $position = Position::where('id', $decryptedID)->with('position_permissions')->first();

        return response()->json([
            'title'     => 'Position',
            'sub-title' => 'Position Data Fetched Successfully',
            'success'   => true,
            'data'      => $position,
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/positions/{id}",
     *     tags={"Position"},
     *     summary="Update a Position by ID",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     )
     * )
     */
    /**
     * @OA\Put(
     *     path="/api/positions/{position}",
     *     tags={"Position"},
     *     summary="Update a {position} by ID",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     )
     * )
     */
    public function update(Request $request)
    {
        $request->validate([
            'name'     => 'required|max:100',
            'role_id'  => 'required',
        ]);

        $decryptedID = $request->id;
        $position = Position::where('id', $decryptedID)->with('position_permissions')->first();

        // Update position basic data
        $position->update([
            'name' => $request->name,
            'role_id' => $request->role_id,
        ]);

        // Handle position permissions if provided
        if ($request->has('position_permissions')) {
            $positionPermissionIdResponseArray = isset($request->position_permissions)
                ? Arr::pluck($request->position_permissions, 'id')
                : [];

            $positionId = $position->id;
            $positionPermissionIdArray = PositionPermission::where('position_id', $positionId)->pluck('id')->toArray();

            // Delete removed permissions
            $differencePositionPermissionIds = array_diff($positionPermissionIdArray, $positionPermissionIdResponseArray);
            if (!empty($differencePositionPermissionIds)) {
                PositionPermission::whereIn('id', $differencePositionPermissionIds)->delete();
            }

            // Update or create Position Permissions
            if (!empty($request->position_permissions)) {
                foreach ($request->position_permissions as $permission) {
                    if (empty($permission['id'])) {
                        $position_permission = new PositionPermission($permission);
                        $position->position_permissions()->save($position_permission);
                    } else {
                        $position_permission = PositionPermission::find($permission['id']);
                        if ($position_permission) {
                            $position_permission->update($permission);
                        }
                    }
                }
            }
        }

        return response()->json([
            'title'     => 'Position',
            'sub-title' => 'Position Data Updated Successfully',
            'success'   => true,
            'data'      => $position->fresh('position_permissions'),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/positions/delete/{id}",
     *     tags={"Position"},
     *     summary="Create a new Delete",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     )
     * )
     */
    public function clear(Request $request)
    {
        $id = $request->id;
        $position = Position::find($id)->update(['is_deleted' => true]);

        return response()->json([
            'title'     => 'Position',
            'sub-title' => 'Position Data Deleted Successfully',
            'success'   => true,
            'data'      => $position,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/positions/restore/{id}",
     *     tags={"Position"},
     *     summary="Create a new Restore",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity"
     *     )
     * )
     */
    public function restore(Request $request)
    {
        $id = $request->id;
        $position = Position::find($id)->update(['is_deleted' => false]);

        return response()->json([
            'title'     => 'Position',
            'sub-title' => 'Position Data Restored Successfully',
            'success'   => true,
            'data'      => $position,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/positions/{id}",
     *     tags={"Position"},
     *     summary="Hard delete a Position",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function destroy(Request $request)
    {
        $id = $request->id;
        Position::find($id)->delete();

        return response()->json([
            'title'     => 'Position',
            'sub-title' => 'Position Data Permanently Deleted Successfully',
            'success'   => true,
            'message'   => 'Position has been permanently deleted',
        ], 200);
    }
}








