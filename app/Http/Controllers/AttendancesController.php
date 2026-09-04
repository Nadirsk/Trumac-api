<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Notification;
use App\Models\User;
use App\Helpers\Utility;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AttendancesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show']);
    }

    /**
     * @OA\Get(
     *     path="/api/attendances",
     *     tags={"Attendances"},
     *     summary="Get all attendances",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = Attendance::with('user.position');
            $query = Utility::prepareSearchQuery($query, $request, new Attendance());
            $query->orderBy('id', 'desc');
            $attendances = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title' => 'Attendances',
                'sub-title' => 'Attendance records fetched successfully',
                'success' => true,
                'data' => $attendances,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/attendances/today",
     *     tags={"Attendances"},
     *     summary="Get today's attendance for current user",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function today(Request $request)
    {
        $attendance = Attendance::where('user_id', $request->user()->id)
            ->where('company_id', $request->company->id)
            ->whereDate('date', Carbon::today())
            ->first();

        return response()->json([
            'title' => 'Attendance',
            'sub-title' => 'Today\'s attendance fetched successfully',
            'success' => true,
            'data' => $attendance,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/attendances/punch-in",
     *     tags={"Attendances"},
     *     summary="Punch in",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function punchIn(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'address' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $today = Carbon::today();

        // Check if already punched in today
        $existingAttendance = Attendance::where('user_id', $user->id)
            ->where('company_id', $request->company->id)
            ->whereDate('date', $today)
            ->first();

        if ($existingAttendance) {
            if ($existingAttendance->punch_in_time && !$existingAttendance->punch_out_time) {
                return response()->json([
                    'title' => 'Attendance',
                    'sub-title' => 'You are already punched in',
                    'success' => false,
                ], 400);
            }

            if ($existingAttendance->punch_out_time) {
                return response()->json([
                    'title' => 'Attendance',
                    'sub-title' => 'You have already completed attendance for today',
                    'success' => false,
                ], 400);
            }
        }

        $attendance = Attendance::updateOrCreate(
            [
                'user_id' => $user->id,
                'company_id' => $request->company->id,
                'date' => $today,
            ],
            [
                'punch_in_time' => Carbon::now(),
                'punch_in_latitude' => $request->latitude,
                'punch_in_longitude' => $request->longitude,
                'punch_in_address' => $request->address,
                'status' => 'present',
            ]
        );

        // Notify admin about punch-in
        $this->notifyAdminAboutAttendance($user, 'Punch In', $request->company->id);

        return response()->json([
            'title' => 'Attendance',
            'sub-title' => 'Punched in successfully',
            'success' => true,
            'data' => $attendance,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/attendances/punch-out",
     *     tags={"Attendances"},
     *     summary="Punch out",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function punchOut(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'address' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('company_id', $request->company->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance || !$attendance->punch_in_time) {
            return response()->json([
                'title' => 'Attendance',
                'sub-title' => 'You need to punch in first',
                'success' => false,
            ], 400);
        }

        if ($attendance->punch_out_time) {
            return response()->json([
                'title' => 'Attendance',
                'sub-title' => 'You have already punched out',
                'success' => false,
            ], 400);
        }

        $attendance->update([
            'punch_out_time' => Carbon::now(),
            'punch_out_latitude' => $request->latitude,
            'punch_out_longitude' => $request->longitude,
            'punch_out_address' => $request->address,
        ]);

        $attendance->calculateTotalHours();

        // Check if half day (less than 4 hours)
        if ($attendance->total_hours < 4) {
            $attendance->update(['status' => 'half_day']);
        }

        // Notify admin about punch-out
        $this->notifyAdminAboutAttendance($user, 'Punch Out', $request->company->id);

        return response()->json([
            'title' => 'Attendance',
            'sub-title' => 'Punched out successfully',
            'success' => true,
            'data' => $attendance->fresh(),
        ], 200);
    }

    /**
     * Notify the user's admin about attendance punch in/out
     */
    private function notifyAdminAboutAttendance($user, $action, $companyId)
    {
        $roleId = $user->position?->role_id;
        if (!$roleId) return;

        // Find admin users of the same role group
        $adminRoleMap = [
            4 => 4, // IT positions -> IT Admin
            5 => 5, // Warehouse positions -> Warehouse Admin
            6 => 6, // CG positions -> CG Admin
            7 => 7, // Franchise positions -> Franchise Admin
        ];

        $adminRoleId = $adminRoleMap[$roleId] ?? null;
        if (!$adminRoleId) return;

        $adminUsers = User::whereHas('position', function ($q) use ($adminRoleId) {
            $q->where('role_id', $adminRoleId);
        })
            ->whereHas('companies', function ($q) use ($companyId) {
                $q->where('companies.id', $companyId);
            })
            ->where('id', '!=', $user->id)
            ->pluck('id')
            ->toArray();

        if (!empty($adminUsers)) {
            $userName = $user->first_name . ' ' . $user->last_name;
            Notification::sendToMany(
                $adminUsers,
                $action . ': ' . $userName,
                $userName . ' has ' . strtolower($action) . ' at ' . now()->format('h:i A'),
                'attendance',
                ['user_id' => $user->id, 'action' => strtolower(str_replace(' ', '_', $action))],
                $companyId
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/attendances/history",
     *     tags={"Attendances"},
     *     summary="Get attendance history for current user",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $query = Attendance::where('user_id', $user->id)
            ->where('company_id', $request->company->id)
            ->where('is_deleted', false)
            ->orderBy('date', 'desc');

        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('date', $request->month)
                ->whereYear('date', $request->year);
        }

        $count = $query->count();

        if ($request->filled('page') && $request->filled('rowsPerPage')) {
            $items = $query->paginate($request->rowsPerPage)->items();
        } else {
            $items = $query->limit(30)->get();
        }

        return response()->json([
            'title' => 'Attendance History',
            'sub-title' => 'Attendance history fetched successfully',
            'success' => true,
            'count' => $count,
            'data' => $items,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/attendances/summary",
     *     tags={"Attendances"},
     *     summary="Get attendance summary for current user",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function summary(Request $request)
    {
        $user = $request->user();
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $attendances = Attendance::where('user_id', $user->id)
            ->where('company_id', $request->company->id)
            ->where('is_deleted', false)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $summary = [
            'total_days' => $attendances->count(),
            'present_days' => $attendances->where('status', 'present')->count(),
            'half_days' => $attendances->where('status', 'half_day')->count(),
            'leave_days' => $attendances->where('status', 'leave')->count(),
            'absent_days' => $attendances->where('status', 'absent')->count(),
            'total_hours' => round($attendances->sum('total_hours'), 2),
            'average_hours' => $attendances->count() > 0
                ? round($attendances->sum('total_hours') / $attendances->count(), 2)
                : 0,
        ];

        return response()->json([
            'title' => 'Attendance Summary',
            'sub-title' => 'Attendance summary fetched successfully',
            'success' => true,
            'data' => $summary,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/attendances/{id}",
     *     tags={"Attendances"},
     *     summary="Get single attendance record",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Request $request)
    {
        $attendance = Attendance::with(['user:id,first_name,last_name,email,image_path'])
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$attendance) {
            return response()->json([
                'title' => 'Attendance',
                'sub-title' => 'Attendance record not found',
                'success' => false,
            ], 404);
        }

        return response()->json([
            'title' => 'Attendance',
            'sub-title' => 'Attendance record fetched successfully',
            'success' => true,
            'data' => $attendance,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/attendances/team",
     *     tags={"Attendances"},
     *     summary="Get today's attendance for team (for managers)",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function team(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());

        $attendances = Attendance::with(['user:id,first_name,last_name,email,image_path,position_id'])
            ->where('company_id', $request->company->id)
            ->where('is_deleted', false)
            ->whereDate('date', $date)
            ->get();

        // Get all users who haven't marked attendance
        $markedUserIds = $attendances->pluck('user_id')->toArray();

        $notMarked = \App\Models\User::whereHas('companies', function ($query) use ($request) {
                $query->where('companies.id', $request->company->id);
            })
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->whereNotIn('id', $markedUserIds)
            ->select('id', 'first_name', 'last_name', 'email', 'image_path', 'position_id')
            ->get();

        return response()->json([
            'title' => 'Team Attendance',
            'sub-title' => 'Team attendance fetched successfully',
            'success' => true,
            'data' => [
                'marked' => $attendances,
                'not_marked' => $notMarked,
                'summary' => [
                    'total_marked' => $attendances->count(),
                    'punched_in' => $attendances->whereNotNull('punch_in_time')->whereNull('punch_out_time')->count(),
                    'punched_out' => $attendances->whereNotNull('punch_out_time')->count(),
                    'not_marked' => $notMarked->count(),
                ],
            ],
        ], 200);
    }
}
