<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Value;
use App\Models\ValueList;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ValueListsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'clear', 'restore']);
    }


    /**
     * @OA\Get(
     *     path="/api/values/{value}/value_lists",
     *     tags={"Value"},
     *     summary="Get all Value_lists",
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
    public function index(Request $request, Value $value)
    {
        try {
            $query = ValueList::query()->where('value_id', $value->id);
            $query = Utility::prepareSearchQuery($query, $request, new ValueList());
            $query->orderBy('id', 'desc');
            $valueLists = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title'     => 'Value List',
                'sub-title' => 'Value List Data Fetched Successfully',
                'success'   => true,
                'data'      => $valueLists,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/values/{value}/value_lists",
     *     tags={"Value"},
     *     summary="Create a new Value_lists",
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
    public function store(Request $request, Value $value)
    {
        $request->validate([
            'value_id' => 'required',
            'description' => 'required|max:100',
            'code' => 'required|max:100',
        ]);

        $value_list = new ValueList($request->all());
        $value_list->company_id = $request->company->id;
        $value->value_lists()->save($value_list);

        return response()->json([
            'title'     => 'Value List',
            'sub-title' => 'Value List Data Stored Successfully',
            'success'   => true,
            'data'      => $value_list,
        ], 200);
    }


    /**
     * @OA\Post(
     *     path="/api/values/{value}/multiple_value_lists",
     *     tags={"Value"},
     *     summary="Create a new Multiple_value_lists",
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
    public function storeMultiple(Request $request, Value $value)
    {
        $request->validate([
            'datas' => 'required|array',
            'datas.*.description' => 'required|string|max:100',
            'datas.*.code' => 'required|string|max:100',
        ]);

        $value_lists = [];

        $existingValueListIds = $value->value_lists()->pluck('id')->toArray();

        foreach ($request['datas'] as $valueListData) {
            if (empty($valueListData['id'])) {
                $value_list = new ValueList($valueListData);
                $value_list->company_id = $request->company->id;
                $value->value_lists()->save($value_list);
            } else {
                $value_list = ValueList::findOrFail($valueListData['id']);
                $value_list->update($valueListData);
            }

            $incomingValueListIds[] = $value_list->id;
            $value_lists[] = $value_list;
        }

        $idsToDelete = array_diff($existingValueListIds, $incomingValueListIds);
        if ($idsToDelete) {
            ValueList::whereIn('id', $idsToDelete)->delete();
        }

        return response()->json([
            'title'     => 'Value List',
            'sub-title' => 'Multi Value List Data Stored Successfully',
            'success'   => true,
            'data'      => $value_lists,
        ], 201);
    }


    /**
     * @OA\Get(
     *     path="/api/values/{value}/value_lists/{id}",
     *     tags={"Value"},
     *     summary="Get a Value_list by ID",
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
     *     path="/api/values/{value}/value_lists/{value_list}",
     *     tags={"Value"},
     *     summary="Get all {value_list}",
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
        $value_list = ValueList::where('id', $decryptedID)->where('is_deleted', false)->first();

        return response()->json([
            'title'     => 'Value List',
            'sub-title' => 'Value List Data Fetched Successfully',
            'success'   => true,
            'data'      => $value_list,
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/values/{value}/value_lists/{id}",
     *     tags={"Value"},
     *     summary="Update a Value_list by ID",
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
     *     path="/api/values/{value}/value_lists/{value_list}",
     *     tags={"Value"},
     *     summary="Update a {value_list} by ID",
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
            'value_id' => 'required',
            'description' => 'required|max:100',
            'code' => 'required|max:100',
        ]);

        $decryptedID = $request->id;
        $value_list = ValueList::where('id', $decryptedID)->first();
        $value_list->update($request->all());

        return response()->json([
            'title'     => 'Value List',
            'sub-title' => 'Value List Data Updated Successfully',
            'success'   => true,
            'data'      => $value_list,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/values/{value}/value_lists/delete/{id}",
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
        $value_list = ValueList::find($id)->update(['is_deleted' => true]);

        return response()->json([
            'title'     => 'Value List',
            'sub-title' => 'Value List Data Restored Successfully',
            'success'   => true,
            'data'      => $value_list,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/values/{value}/value_lists/restore/{id}",
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
        $value_list = ValueList::find($id)->update(['is_deleted' => false]);

        return response()->json([
            'title'     => 'Value List',
            'sub-title' => 'Value List Data Restored Successfully',
            'success'   => true,
            'data'      => $value_list,
        ], 200);
    }
}









