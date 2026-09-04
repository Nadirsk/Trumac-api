<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use App\Helpers\Utility;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LeaveTypesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'clear', 'restore']);
    }

    /**
     * @OA\Get(
     *     path="/api/leave-types",
     *     tags={"Leave Types"},
     *     summary="Get all leave types",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = LeaveType::query();
            $query = Utility::prepareSearchQuery($query, $request, new LeaveType());
            $query->orderBy('id', 'desc');
            $leaveTypes = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title' => 'Leave Types',
                'sub-title' => 'Leave types fetched successfully',
                'success' => true,
                'data' => $leaveTypes,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/leave-types",
     *     tags={"Leave Types"},
     *     summary="Create a new leave type",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'name' => 'required|string|max:100',
            'is_paid' => 'required|boolean',
            'max_days' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $attributes['company_id'] = $request->company->id;

        $leaveType = LeaveType::create($attributes);

        return response()->json([
            'title' => 'Leave Type',
            'sub-title' => 'Leave type created successfully',
            'success' => true,
            'data' => $leaveType,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/leave-types/{id}",
     *     tags={"Leave Types"},
     *     summary="Get single leave type",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Request $request)
    {
        $leaveType = LeaveType::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$leaveType) {
            return response()->json([
                'title' => 'Leave Type',
                'sub-title' => 'Leave type not found',
                'success' => false,
            ], 404);
        }

        return response()->json([
            'title' => 'Leave Type',
            'sub-title' => 'Leave type fetched successfully',
            'success' => true,
            'data' => $leaveType,
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/leave-types/{id}",
     *     tags={"Leave Types"},
     *     summary="Update a leave type",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(Request $request)
    {
        $leaveType = LeaveType::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$leaveType) {
            return response()->json([
                'title' => 'Leave Type',
                'sub-title' => 'Leave type not found',
                'success' => false,
            ], 404);
        }

        $attributes = $request->validate([
            'name' => 'required|string|max:100',
            'is_paid' => 'required|boolean',
            'max_days' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $leaveType->update($attributes);

        return response()->json([
            'title' => 'Leave Type',
            'sub-title' => 'Leave type updated successfully',
            'success' => true,
            'data' => $leaveType,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/leave-types/delete/{id}",
     *     tags={"Leave Types"},
     *     summary="Soft delete a leave type",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function clear(Request $request)
    {
        $leaveType = LeaveType::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$leaveType) {
            return response()->json([
                'title' => 'Leave Type',
                'sub-title' => 'Leave type not found',
                'success' => false,
            ], 404);
        }

        $leaveType->update(['is_deleted' => true]);

        return response()->json([
            'title' => 'Leave Type',
            'sub-title' => 'Leave type deleted successfully',
            'success' => true,
            'data' => $leaveType,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/leave-types/restore/{id}",
     *     tags={"Leave Types"},
     *     summary="Restore a soft-deleted leave type",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function restore(Request $request)
    {
        $leaveType = LeaveType::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$leaveType) {
            return response()->json([
                'title' => 'Leave Type',
                'sub-title' => 'Leave type not found',
                'success' => false,
            ], 404);
        }

        $leaveType->update(['is_deleted' => false]);

        return response()->json([
            'title' => 'Leave Type',
            'sub-title' => 'Leave type restored successfully',
            'success' => true,
            'data' => $leaveType,
        ], 200);
    }
}
