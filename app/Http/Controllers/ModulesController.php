<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Module;
use App\Models\Permission;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ModulesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company', 'allowed_urls']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'clear', 'restore']);
    }


    /**
     * @OA\Get(
     *     path="/api/modules",
     *     tags={"Module"},
     *     summary="Get all Modules",
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
            $query = Module::query();
            $query = Utility::prepareSearchQuery($query, $request, new Module());
            $query->orderBy('id', 'desc');
            $modules = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title'     => 'Module',
                'sub-title' => 'Module Data Fetched Successfully',
                'success'   => true,
                'data'      => $modules,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/modules",
     *     tags={"Module"},
     *     summary="Create a new Modules",
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

        $module = $request->company->modules()->create(['name' => $request->name]);

        $permissionsData = [
            ['name' => 'VIEW'],
            ['name' => 'SINGLE_VIEW'],
            ['name' => 'CREATE'],
            ['name' => 'UPDATE'],
            ['name' => 'DELETE'],
            ['name' => 'MASTERS']
        ];

        $permissions = $module->permissions()->createMany(
            array_map(fn($permission) => array_merge($permission, ['company_id' => $request->company->id]), $permissionsData)
        );

        if ($module->name == 'USERS') {
            $permissions->whereIn('name', ['SINGLE_VIEW', 'UPDATE'])->each(function ($permission) use ($request) {
                // Assign the positions with these permissions
                $request->company->positions()->each(function ($position) use ($permission) {
                    $permission->position_permissions()->create([
                        'position_id' => $position->id
                    ]);
                });
            });
        }

        return response()->json([
            'title'     => 'Module',
            'sub-title' => 'Module Data Stored Successfully',
            'success'   => true,
            'data'      => $module,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/modules/{id}",
     *     tags={"Module"},
     *     summary="Get a Module by ID",
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
     *     path="/api/modules/{module}",
     *     tags={"Module"},
     *     summary="Get all {module}",
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
        $module = Module::where('id', $decryptedID)->where('is_deleted', false)->first();

        if (!$module) {
            return response()->json([
                'title'     => 'Module',
                'sub-title' => 'Module Not Found',
                'success'   => false,
                'message'   => 'Module data not found or is deleted.',
            ], 404);
        }

        return response()->json([
            'title'     => 'Module',
            'sub-title' => 'Module Data Fetched Successfully',
            'success'   => true,
            'data'      => $module,
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/modules/{id}",
     *     tags={"Module"},
     *     summary="Update a Module by ID",
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
     *     path="/api/modules/{module}",
     *     tags={"Module"},
     *     summary="Update a {module} by ID",
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
            'first_name'    => 'required|max:100',
            'last_name'     => 'required|max:100',
            'email'         => 'required|max:100',
            'phone'         => 'required',
        ]);

        $decryptedID = $request->id;
        $module = Module::where('id', $decryptedID)->first();
        $module->update($request->all());

        return response()->json([
            'title'     => 'Module',
            'sub-title' => 'Module Data Updated Successfully',
            'success'   => true,
            'data'      => $module,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/modules/delete/{id}",
     *     tags={"Module"},
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
        $module = Module::find($id)->update(['is_deleted' => true]);

        return response()->json([
            'title'     => 'Module',
            'sub-title' => 'Module Data Restored Successfully',
            'success'   => true,
            'data'      => $module,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/modules/restore/{id}",
     *     tags={"Module"},
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
        $module = Module::find($id)->update(['is_deleted' => false]);

        return response()->json([
            'title'     => 'Module',
            'sub-title' => 'Module Data Restored Successfully',
            'success'   => true,
            'data'      => $module,
        ], 200);
    }
}








