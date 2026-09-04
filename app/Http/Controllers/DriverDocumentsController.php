<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\DriverDocument;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DriverDocumentsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'clear', 'restore']);
    }

    /**
     * @OA\Get(
     *     path="/api/driver_documents",
     *     tags={"Driver_document"},
     *     summary="Get all Driver_documents",
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
            $query = DriverDocument::query();
            $query = Utility::prepareSearchQuery($query, $request, new DriverDocument());
            $query->orderBy('id', 'desc');
            $driverDocuments = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title'     => 'DriverDocument',
                'sub-title' => 'DriverDocument listing Fetched Successfully',
                'success'   => true,
                'data'      => $driverDocuments,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/driver_documents",
     *     tags={"Driver_document"},
     *     summary="Create a new Driver_documents",
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
            'user_id'      => 'required',
            'doc_type_id'  => 'required|max:100',
        ]);

        $driver_document = $request->company->driver_documents()->create($request->except(['doc_front_path', 'doc_back_path']));

        return response()->json([
            'title'     => 'DriverDocument',
            'sub-title' => 'DriverDocument Data Stored Successfully',
            'success'   => true,
            'data'      => $driver_document,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/driver_documents/{id}",
     *     tags={"Driver_document"},
     *     summary="Get a Driver_document by ID",
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
     *     path="/api/driver_documents/{driver_document}",
     *     tags={"Driver_document"},
     *     summary="Get all {driver_document}",
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
        $driver_document = DriverDocument::where('id', $decryptedID)->first();

        return response()->json([
            'title'     => 'DriverDocument',
            'sub-title' => 'DriverDocument Data Fetched Successfully',
            'success'   => true,
            'data'      => $driver_document,
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/driver_documents/{id}",
     *     tags={"Driver_document"},
     *     summary="Update a Driver_document by ID",
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
     *     path="/api/driver_documents/{driver_document}",
     *     tags={"Driver_document"},
     *     summary="Update a {driver_document} by ID",
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
            'user_id'      => 'required',
            'doc_type_id'  => 'required|max:100',
        ]);

        $decryptedID = $request->id;
        $driver_document = DriverDocument::where('id', $decryptedID)->first();
        $data = $request->all();
        $driver_document->update($data);

        return response()->json([
            'title'     => 'DriverDocument',
            'sub-title' => 'DriverDocument Data Updated Successfully',
            'success'   => true,
            'data'      => $driver_document,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/driver_documents/delete/{id}",
     *     tags={"Driver_document"},
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
        $driver_document = DriverDocument::find($id)->update(['is_deleted' => true]);

        return response()->json([
            'title'     => 'DriverDocument',
            'sub-title' => 'DriverDocument Data Deleted Successfully',
            'success'   => true,
            'data'      => $driver_document,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/driver_documents/restore/{id}",
     *     tags={"Driver_document"},
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
        $driver_document = DriverDocument::find($id)->update(['is_deleted' => false]);

        return response()->json([
            'title'     => 'DriverDocument',
            'sub-title' => 'DriverDocument Data Restored Successfully',
            'success'   => true,
            'data'      => $driver_document,
        ], 200);
    }
}








