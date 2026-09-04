<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Value;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ValuesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'clear', 'restore', 'destroy']);
    }


    /**
     * @OA\Get(
     *     path="/api/values",
     *     tags={"Value"},
     *     summary="Get all Values",
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
            $query = Value::query();
            $query = Utility::prepareSearchQuery($query, $request, new Value());
            $query->orderBy('id', 'desc');
            $values = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title' => 'Value',
                'sub-title' => 'Value Data Fetched Successfully',
                'success' => true,
                'data' => $values,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/values",
     *     tags={"Value"},
     *     summary="Create a new Values",
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
            'name' => 'required|max:100'
        ]);

        $value = $request->company->values()->create(['name' => $request->name]);

        return response()->json([
            'title' => 'Value',
            'sub-title' => 'Value Data Stored Successfully',
            'success' => true,
            'data' => $value,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/values/{id}",
     *     tags={"Value"},
     *     summary="Get a Value by ID",
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
     *     path="/api/values/{value}",
     *     tags={"Value"},
     *     summary="Get all {value}",
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
        $value = Value::where('id', $decryptedID)->where('is_deleted', false)->first();

        if (!$value) {
            return response()->json([
                'title' => 'Value',
                'sub-title' => 'Value Not Found',
                'success' => false,
                'message' => 'Value data not found or is deleted.',
            ], 404);
        }

        return response()->json([
            'title' => 'Value',
            'sub-title' => 'Value Data Fetched Successfully',
            'success' => true,
            'data' => $value,
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/values/{id}",
     *     tags={"Value"},
     *     summary="Update a Value by ID",
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
     *     path="/api/values/{value}",
     *     tags={"Value"},
     *     summary="Update a {value} by ID",
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
            'name' => 'required|max:100',
        ]);

        $decryptedID = $request->id;
        $value = Value::where('id', $decryptedID)->first();
        $value->update($request->all());

        return response()->json([
            'title' => 'Value',
            'sub-title' => 'Value Data Updated Successfully',
            'success' => true,
            'data' => $value,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/values/delete/{id}",
     *     tags={"Value"},
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
        $value = Value::find($id)->update(['is_deleted' => true]);

        return response()->json([
            'title' => 'Value',
            'sub-title' => 'Value Data Deleted Successfully',
            'success' => true,
            'data' => $value,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/values/restore/{id}",
     *     tags={"Value"},
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
        $value = Value::find($id)->update(['is_deleted' => false]);

        return response()->json([
            'title' => 'Value',
            'sub-title' => 'Value Data Restored Successfully',
            'success' => true,
            'data' => $value,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/values/{id}",
     *     tags={"Value"},
     *     summary="Delete a Value permanently",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found"
     *     )
     * )
     */
    public function destroy(Request $request)
    {
        $id = $request->id;
        $value = Value::find($id);

        if (!$value) {
            return response()->json([
                'title' => 'Value',
                'sub-title' => 'Value Not Found',
                'success' => false,
                'message' => 'Value not found.',
            ], 404);
        }

        $value->delete();

        return response()->json([
            'title' => 'Value',
            'sub-title' => 'Value Data Deleted Successfully',
            'success' => true,
            'message' => 'Value deleted successfully.',
        ], 200);
    }
}








