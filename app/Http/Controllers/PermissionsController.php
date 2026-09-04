<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Permission;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PermissionsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company', 'allowed_urls']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'clear', 'restore']);
    }


    /**
     * @OA\Get(
     *     path="/api/permissions",
     *     tags={"Permission"},
     *     summary="Get all Permissions",
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
            $query = Permission::query();
            $query = Utility::prepareSearchQuery($query, $request, new Permission());
            $query->orderBy('id', 'desc');
            $permissions = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title'     => 'Permission',
                'sub-title' => 'Permission Data Fetched Successfully',
                'success'   => true,
                'data'      => $permissions,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/permissions",
     *     tags={"Permission"},
     *     summary="Create a new Permissions",
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
            'name' => 'required|max:50'
        ]);

        $permission = $request->company->permissions()->create($request->all());

        return response()->json([
            'title'     => 'Permission',
            'sub-title' => 'Permission Data Stored Successfully',
            'success'   => true,
            'data'      => $permission,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/permissions/{id}",
     *     tags={"Permission"},
     *     summary="Get a Permission by ID",
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
     *     path="/api/permissions/{permission}",
     *     tags={"Permission"},
     *     summary="Get all {permission}",
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
        $permission = Permission::where('id', $decryptedID)->where('is_deleted', false)->first();

        if (!$permission) {
            return response()->json([
                'title'     => 'Permission',
                'sub-title' => 'Permission Not Found',
                'success'   => false,
                'message'   => 'Permission data not found or is deleted.',
            ], 404);
        }

        return response()->json([
            'title'     => 'Permission',
            'sub-title' => 'Permission Data Fetched Successfully',
            'success'   => true,
            'data'      => $permission,
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/permissions/{id}",
     *     tags={"Permission"},
     *     summary="Update a Permission by ID",
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
     *     path="/api/permissions/{permission}",
     *     tags={"Permission"},
     *     summary="Update a {permission} by ID",
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
            'name'    => 'required|max:100',
        ]);

        $decryptedID = $request->id;
        $permission = Permission::where('id', $decryptedID)->first();
        $permission->update($request->all());

        return response()->json([
            'title'     => 'Permission',
            'sub-title' => 'Permission Data Updated Successfully',
            'success'   => true,
            'data'      => $permission,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/permissions/delete/{id}",
     *     tags={"Permission"},
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
        $permission = Permission::find($id)->update(['is_deleted' => true]);

        return response()->json([
            'title'     => 'Permission',
            'sub-title' => 'Permission Data Restored Successfully',
            'success'   => true,
            'data'      => $permission,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/permissions/restore/{id}",
     *     tags={"Permission"},
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
        $permission = Permission::find($id)->update(['is_deleted' => false]);

        return response()->json([
            'title'     => 'Permission',
            'sub-title' => 'Permission Data Restored Successfully',
            'success'   => true,
            'data'      => $permission,
        ], 200);
    }
}








