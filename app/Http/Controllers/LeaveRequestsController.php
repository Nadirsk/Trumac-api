<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Notification;
use App\Models\RegularisationRequest;
use App\Models\User;
use App\Helpers\Utility;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class LeaveRequestsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'approve', 'reject']);
    }

    /**
     * @OA\Get(
     *     path="/api/leave-requests",
     *     tags={"Leave Requests"},
     *     summary="Get all leave requests",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = LeaveRequest::with(['user:id,first_name,last_name,email,image_path,position_id', 'user.position:id,name', 'leaveType:id,name,is_paid'])
                ->where('company_id', $request->company->id)
                ->where('is_deleted', false);

            $query = Utility::prepareSearchQuery($query, $request, new LeaveRequest());
            $query->orderBy('id', 'desc');
            $leaveRequests = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title' => 'Leave Requests',
                'sub-title' => 'Leave requests fetched successfully',
                'success' => true,
                'data' => $leaveRequests,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/leave-requests",
     *     tags={"Leave Requests"},
     *     summary="Create a new leave request",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'from_date' => 'required|date|after_or_equal:today',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'required|string|max:1000',
        ]);

        $attributes['user_id'] = $request->user()->id;
        $attributes['status'] = 'pending';
        $attributes['company_id'] = $request->company->id;

        // Check for overlapping leave requests
        $overlap = LeaveRequest::where('user_id', $attributes['user_id'])
            ->where('is_deleted', false)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($attributes) {
                $q->whereBetween('from_date', [$attributes['from_date'], $attributes['to_date']])
                    ->orWhereBetween('to_date', [$attributes['from_date'], $attributes['to_date']])
                    ->orWhere(function ($q2) use ($attributes) {
                        $q2->where('from_date', '<=', $attributes['from_date'])
                            ->where('to_date', '>=', $attributes['to_date']);
                    });
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'title' => 'Leave Request',
                'sub-title' => 'You already have a leave request for this period',
                'success' => false,
            ], 400);
        }

        $leaveRequest = LeaveRequest::create($attributes);

        // Notify admin about new leave request
        $user = $request->user();
        $userName = $user->first_name . ' ' . $user->last_name;
        $roleId = $user->position?->role_id;

        if ($roleId) {
            // Admin roles (4,5,6,7) → notify Super Admin (1,2)
            // Sub-positions → notify their admin role
            $adminUsers = User::whereHas('position', function ($q) use ($roleId) {
                $q->where('role_id', $roleId);
            })
                ->whereHas('companies', fn($q) => $q->where('companies.id', $request->company->id))
                ->where('id', '!=', $user->id)
                ->pluck('id')
                ->toArray();

            // Also notify Super Admin/Admin for admin-level roles
            if (in_array($roleId, [4, 5, 6, 7])) {
                $superAdmins = User::whereHas('roles', fn($q) => $q->whereIn('roles.id', [1, 2]))
                    ->whereHas('companies', fn($q) => $q->where('companies.id', $request->company->id))
                    ->pluck('id')
                    ->toArray();
                $adminUsers = array_unique(array_merge($adminUsers, $superAdmins));
            }

            if (!empty($adminUsers)) {
                $fromDate = Carbon::parse($attributes['from_date'])->format('d M');
                $toDate = Carbon::parse($attributes['to_date'])->format('d M');
                Notification::sendToMany(
                    $adminUsers,
                    'Leave Request: ' . $userName,
                    $userName . ' has requested leave from ' . $fromDate . ' to ' . $toDate,
                    'leave_request',
                    ['leave_request_id' => $leaveRequest->id, 'action' => 'submitted', 'user_id' => $user->id],
                    $request->company->id
                );
            }
        }

        return response()->json([
            'title' => 'Leave Request',
            'sub-title' => 'Leave request submitted successfully',
            'success' => true,
            'data' => $leaveRequest->load(['leaveType:id,name,is_paid']),
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/leave-requests/{id}",
     *     tags={"Leave Requests"},
     *     summary="Get single leave request",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Request $request)
    {
        $leaveRequest = LeaveRequest::with(['user:id,first_name,last_name,email,image_path', 'leaveType:id,name,is_paid,max_days', 'approver:id,first_name,last_name'])
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$leaveRequest) {
            return response()->json([
                'title' => 'Leave Request',
                'sub-title' => 'Leave request not found',
                'success' => false,
            ], 404);
        }

        return response()->json([
            'title' => 'Leave Request',
            'sub-title' => 'Leave request fetched successfully',
            'success' => true,
            'data' => $leaveRequest,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/leave-requests/{id}/approve",
     *     tags={"Leave Requests"},
     *     summary="Approve a leave request",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function approve(Request $request)
    {
        $leaveRequest = LeaveRequest::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$leaveRequest) {
            return response()->json([
                'title' => 'Leave Request',
                'sub-title' => 'Leave request not found',
                'success' => false,
            ], 404);
        }

        if ($leaveRequest->status !== 'pending') {
            return response()->json([
                'title' => 'Leave Request',
                'sub-title' => 'Leave request is already ' . $leaveRequest->status,
                'success' => false,
            ], 400);
        }

        $updateData = [
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'remarks' => $request->input('remarks'),
        ];

        // Allow admin to update leave type if provided
        if ($request->filled('leave_type_id')) {
            $updateData['leave_type_id'] = $request->leave_type_id;
        }

        $leaveRequest->update($updateData);

        // Notify user about approval
        $approverName = $request->user()->first_name . ' ' . $request->user()->last_name;
        Notification::send(
            $leaveRequest->user_id,
            'Leave Approved',
            'Your leave request has been approved by ' . $approverName,
            'leave_request',
            ['leave_request_id' => $leaveRequest->id, 'action' => 'approved'],
            $request->company->id
        );

        return response()->json([
            'title' => 'Leave Request',
            'sub-title' => 'Leave request approved successfully',
            'success' => true,
            'data' => $leaveRequest->fresh(['user:id,first_name,last_name', 'leaveType:id,name', 'approver:id,first_name,last_name']),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/leave-requests/{id}/reject",
     *     tags={"Leave Requests"},
     *     summary="Reject a leave request",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function reject(Request $request)
    {
        $request->validate([
            'remarks' => 'required|string|max:500',
        ]);

        $leaveRequest = LeaveRequest::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$leaveRequest) {
            return response()->json([
                'title' => 'Leave Request',
                'sub-title' => 'Leave request not found',
                'success' => false,
            ], 404);
        }

        if ($leaveRequest->status !== 'pending') {
            return response()->json([
                'title' => 'Leave Request',
                'sub-title' => 'Leave request is already ' . $leaveRequest->status,
                'success' => false,
            ], 400);
        }

        $updateData = [
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'remarks' => $request->remarks,
        ];

        // Allow admin to update leave type if provided
        if ($request->filled('leave_type_id')) {
            $updateData['leave_type_id'] = $request->leave_type_id;
        }

        $leaveRequest->update($updateData);

        // Notify user about rejection
        $approverName = $request->user()->first_name . ' ' . $request->user()->last_name;
        Notification::send(
            $leaveRequest->user_id,
            'Leave Rejected',
            'Your leave request has been rejected by ' . $approverName . '. Reason: ' . $request->remarks,
            'leave_request',
            ['leave_request_id' => $leaveRequest->id, 'action' => 'rejected'],
            $request->company->id
        );

        return response()->json([
            'title' => 'Leave Request',
            'sub-title' => 'Leave request rejected',
            'success' => true,
            'data' => $leaveRequest->fresh(['user:id,first_name,last_name', 'leaveType:id,name', 'approver:id,first_name,last_name']),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/leave-requests/{id}/cancel",
     *     tags={"Leave Requests"},
     *     summary="Cancel own leave request",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function cancel(Request $request)
    {
        $leaveRequest = LeaveRequest::where('id', $request->id)
            ->where('user_id', $request->user()->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$leaveRequest) {
            return response()->json([
                'title' => 'Leave Request',
                'sub-title' => 'Leave request not found',
                'success' => false,
            ], 404);
        }

        if ($leaveRequest->status !== 'pending') {
            return response()->json([
                'title' => 'Leave Request',
                'sub-title' => 'Only pending requests can be cancelled',
                'success' => false,
            ], 400);
        }

        $leaveRequest->update(['status' => 'cancelled']);

        return response()->json([
            'title' => 'Leave Request',
            'sub-title' => 'Leave request cancelled',
            'success' => true,
            'data' => $leaveRequest,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/leave-requests/my-requests",
     *     tags={"Leave Requests"},
     *     summary="Get current user's leave requests",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function myRequests(Request $request)
    {
        $query = LeaveRequest::with(['leaveType:id,name,is_paid', 'approver:id,first_name,last_name'])
            ->where('user_id', $request->user()->id)
            ->where('company_id', $request->company->id)
            ->where('is_deleted', false)
            ->orderBy('id', 'desc');
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $count = $query->count();

        if ($request->filled('page') && $request->filled('rowsPerPage')) {
            $items = $query->paginate($request->rowsPerPage)->items();
        } else {
            $items = $query->limit(20)->get();
        }

        return response()->json([
            'title' => 'My Leave Requests',
            'sub-title' => 'Leave requests fetched successfully',
            'success' => true,
            'count' => $count,
            'data' => $items,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/leave-requests/pending-approvals",
     *     tags={"Leave Requests"},
     *     summary="Get pending leave requests for approval",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function pendingApprovals(Request $request)
    {
        $loggedInUser = $request->user();
        $loggedInUserId = $loggedInUser->id;
        $firstRole = $loggedInUser->roles()->first();
        $userRoleId = $firstRole ? $firstRole->id : null;

        $query = LeaveRequest::with(['user:id,first_name,last_name,email,image_path,position_id', 'user.position:id,name,role_id', 'leaveType:id,name,is_paid'])
            ->where('company_id', $request->company->id)
            ->where('is_deleted', false)
            ->where('status', 'pending')
            ->where('user_id', '!=', $loggedInUserId); // Exclude logged-in user's own requests

        // Role-based filtering for subordinates
        // Role ID 2: ADMIN - can see all
        // Role ID 4: IT ADMIN - can see IT subordinates (IT Team Leader, IT Employee, IT Driver, IT Purchase)
        // Role ID 5: WAREHOUSE ADMIN - can see Warehouse subordinates (Warehouse Billing, Warehouse Driver)
        // Role ID 6: CG ADMIN - can see CG subordinates (CG Billing, CG Driver)
        // Role ID 7: FRANCHISE ADMIN - can see Franchise subordinates (Franchise Billing, Franchise Driver)
        if ($userRoleId && !in_array($userRoleId, [1, 2])) { // Not SUPER ADMIN or ADMIN
            $positionName = $loggedInUser->position ? $loggedInUser->position->name : null;
            $subordinatePositionNames = $this->getSubordinatePositions($userRoleId, $positionName);

            if (!empty($subordinatePositionNames)) {
                $query->whereHas('user.position', function ($q) use ($subordinatePositionNames) {
                    $q->whereIn('name', $subordinatePositionNames);
                });
            } else {
                // If no subordinates defined, return empty
                $query->whereRaw('1 = 0');
            }
        }

        $query->orderBy('id', 'desc');
        $leaveRequests = Utility::getSearchRequestQueryResults($request, $query);

        return response()->json([
            'title' => 'Pending Approvals',
            'sub-title' => 'Pending leave requests fetched successfully',
            'success' => true,
            'data' => $leaveRequests,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/leave-requests/processed-approvals",
     *     tags={"Leave Requests"},
     *     summary="Get processed leave requests (approved/rejected) excluding logged-in user's own requests",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function processedApprovals(Request $request)
    {
        $loggedInUser = $request->user();
        $loggedInUserId = $loggedInUser->id;
        $firstRole = $loggedInUser->roles()->first();
        $userRoleId = $firstRole ? $firstRole->id : null;

        $query = LeaveRequest::with(['user:id,first_name,last_name,email,image_path,position_id', 'user.position:id,name,role_id', 'leaveType:id,name,is_paid', 'approver:id,first_name,last_name'])
            ->where('company_id', $request->company->id)
            ->where('is_deleted', false)
            ->whereIn('status', ['approved', 'rejected'])
            ->where('user_id', '!=', $loggedInUserId); // Exclude logged-in user's own requests

        // Role-based filtering for subordinates
        // Role ID 2: ADMIN - can see all
        // Role ID 4: IT ADMIN - can see IT subordinates (IT Team Leader, IT Employee, IT Driver, IT Purchase)
        // Role ID 5: WAREHOUSE ADMIN - can see Warehouse subordinates (Warehouse Billing, Warehouse Driver)
        // Role ID 6: CG ADMIN - can see CG subordinates (CG Billing, CG Driver)
        // Role ID 7: FRANCHISE ADMIN - can see Franchise subordinates (Franchise Billing, Franchise Driver)
        if ($userRoleId && !in_array($userRoleId, [1, 2])) { // Not SUPER ADMIN or ADMIN
            $positionName = $loggedInUser->position ? $loggedInUser->position->name : null;
            $subordinatePositionNames = $this->getSubordinatePositions($userRoleId, $positionName);

            if (!empty($subordinatePositionNames)) {
                $query->whereHas('user.position', function ($q) use ($subordinatePositionNames) {
                    $q->whereIn('name', $subordinatePositionNames);
                });
            } else {
                // If no subordinates defined, return empty
                $query->whereRaw('1 = 0');
            }
        }

        $query->orderBy('updated_at', 'desc');
        $leaveRequests = Utility::getSearchRequestQueryResults($request, $query);

        return response()->json([
            'title' => 'Processed Approvals',
            'sub-title' => 'Processed leave requests fetched successfully',
            'success' => true,
            'data' => $leaveRequests,
        ], 200);
    }

    /**
     * Get subordinate position names based on role and position
     *
     * @param int $roleId
     * @param string|null $positionName
     * @return array
     */
    private function getSubordinatePositions(int $roleId, ?string $positionName): array
    {
        // Define subordinate hierarchy
        $subordinateMap = [
            // Role ID 4: IT ADMIN positions
            4 => [
                'IT Admin' => ['IT Team Leader', 'IT Employee', 'IT Driver', 'IT Purchase'],
                'IT Team Leader' => ['IT Employee', 'IT Driver'],
                'IT Employee' => [],
                'IT Driver' => [],
                'IT Purchase' => [],
            ],
            // Role ID 5: WAREHOUSE ADMIN positions
            5 => [
                'Warehouse Admin' => ['Warehouse Billing', 'Warehouse Driver'],
                'Warehouse Billing' => [],
                'Warehouse Driver' => [],
            ],
            // Role ID 6: CG ADMIN positions
            6 => [
                'CG Admin' => ['CG Billing', 'CG Driver'],
                'CG Billing' => [],
                'CG Driver' => [],
            ],
            // Role ID 7: FRANCHISE ADMIN positions
            7 => [
                'Franchise Admin' => ['Franchise Billing', 'Franchise Driver'],
                'Franchise Billing' => [],
                'Franchise Driver' => [],
            ],
        ];

        if (!isset($subordinateMap[$roleId])) {
            return [];
        }

        if ($positionName && isset($subordinateMap[$roleId][$positionName])) {
            return $subordinateMap[$roleId][$positionName];
        }

        // If position not found but role exists, return all subordinates for that role (fallback for Admin positions)
        return array_merge(...array_values($subordinateMap[$roleId]));
    }
}
