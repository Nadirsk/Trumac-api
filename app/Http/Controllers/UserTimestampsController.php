<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Company;
use App\Models\Role;
use App\Models\UserTimestamp;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserTimestampsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
    }

    /**
     * @OA\Get(
     *     path="/api/user_timestamps",
     *     tags={"User_timestamp"},
     *     summary="Get all User_timestamps",
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
            $query = UserTimestamp::query();
            $query = Utility::prepareSearchQuery($query, $request, new UserTimestamp());
            $userTimestamps = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title'     => 'User Timestamp',
                'sub-title' => 'User Timestamp listing Fetched Successfully',
                'success'   => true,
                'data'      => $userTimestamps,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/user_timestamps",
     *     tags={"User_timestamp"},
     *     summary="Create a new User_timestamps",
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
            'user_id'   => 'required',
            'from_path' => 'required',
        ]);

        $user_timestamps = null;
        $ip = $request->ip();

        // Get MAC Address (Avoiding shell_exec for security reasons)
        $macAddress = null;
        if (PHP_OS_FAMILY === 'Windows') {
            $macAddress = exec('getmac'); // Windows-safe alternative
        } elseif (PHP_OS_FAMILY === 'Linux') {
            $macAddress = exec('cat /sys/class/net/eth0/address'); // Linux-safe alternative
        }

        $companyId = $request->header('company-id');

        if ($request->user_id && $companyId) {
            $user_timestamps = UserTimestamp::create([
                'company_id' => $companyId,
                'user_id'    => $request->user_id,
                'timespent'  => $request->timespent,
                'url'        => $request->from_path,
                'name'       => $request->from_name,
                'old_json'   => $request->old_json,
                'new_json'   => $request->new_json,
            ]);
        }

        return response()->json([
            'title'     => 'User Timestamp',
            'sub-title' => 'User Timestamp Data Stored Successfully',
            'success'   => true,
            'data'      => $user_timestamps,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/user_timestamps/{user_timestamp}",
     *     tags={"User_timestamp"},
     *     summary="Get all {user_timestamp}",
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
        $user_timestamp = UserTimestamp::where('id', $decryptedID)->first();

        return response()->json([
            'title'     => 'User Timestamp',
            'sub-title' => 'User Timestamp Data Fetched Successfully',
            'success'   => true,
            'data'      => $user_timestamp,
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/user_timestamps/{user_timestamp}",
     *     tags={"User_timestamp"},
     *     summary="Update a {user_timestamp} by ID",
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
        $decryptedID = $request->id;
        $user_timestamp = UserTimestamp::where('id', $decryptedID)->first();
        $data = $request->all();
        $user_timestamp->update($data);

        return response()->json([
            'title'     => 'User Timestamp',
            'sub-title' => 'User Timestamp Data Updated Successfully',
            'success'   => true,
            'data'      => $user_timestamp,
        ], 200);
    }
    
    public function report(Request $request)
    {
        $request->validate([
            'company_id' => 'required',
        ]);

        $totalTimeSpentByUsers = 0;
        $totalCountByUsers = 0;

        // Fetch company either from request or via `company_id`
        $company = $request->company ?? Company::findOrFail($request->company_id);
        $user_timestamps = $company->user_timestamps()->with('company');

        // Apply date filters if provided
        if ($dateFilter = $request->input('date_filter')) {
            $user_timestamps->whereDate('created_at', $dateFilter);
        }
        if ($monthYear = $request->input('month_year')) {
            [$year, $month] = explode('-', $monthYear);
            $user_timestamps->whereYear('created_at', $year)->whereMonth('created_at', $month);
        }

        // Filter by role if provided
        if ($roleId = $request->input('role_id')) {
            $role = Role::find($roleId);
            if ($role) {
                $user_timestamps->whereHas('user.roles', fn($q) => $q->where('name', $role->name));
            }
        }

        // Filter by user if provided
        if ($userId = $request->input('user_id')) {
            $user_timestamps->where('user_id', $userId);
        }

        // Handle report type
        switch ($request->input('type')) {
            case 'BASE_REPORT':
                $user_timestamps = $user_timestamps
                    ->select('user_id', DB::raw('count(*) as total'), DB::raw('sum(total_time_spent) as overall_time_spent'))
                    ->groupBy('user_id')
                    ->get();
                break;

            case 'LOG_REPORT':
                if ($request->has(['page', 'rowsPerPage'])) {
                    $baseReport = $this->report(new Request(['company_id' => $request->company_id, 'type' => 'BASE_REPORT']))->getData();
                    $totalTimeSpentByUsers = $baseReport->data[0]->overall_time_spent ?? 0;
                    $totalCountByUsers = $user_timestamps->count();
                    $user_timestamps = $user_timestamps->paginate($request->rowsPerPage)->items();
                } else {
                    $user_timestamps = $user_timestamps->get();
                }
                break;

            case 'URL_REPORT':
                $user_timestamps = $user_timestamps
                    ->select('url', DB::raw('count(*) as total'), DB::raw('sum(total_time_spent) as overall_time_spent'))
                    ->groupBy('url')
                    ->get();
                break;

            default:
                $user_timestamps = $user_timestamps->get();
                break;
        }

        // Final calculations
        $totalTimeSpentByUsers = $user_timestamps->sum('overall_time_spent') ?? 0;
        $totalCountByUsers = $user_timestamps->sum('total') ?? $user_timestamps->count();

        return response()->json([
            'data'            => $user_timestamps,
            'final_timespent' => $totalTimeSpentByUsers,
            'final_count'     => $totalCountByUsers,
            'count'           => $totalCountByUsers
        ], 200);
    }
}




