<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Pjp;
use App\Models\PjpRetailer;
use App\Models\PjpChange;
use App\Helpers\Utility;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Carbon\Carbon;

class PjpsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'destroy', 'clear', 'restore']);
    }

    /**
     * @OA\Get(
     *     path="/api/pjps",
     *     tags={"PJP"},
     *     summary="Get all PJPs",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = Pjp::with(['employee:id,first_name,last_name,email'])
                ->withCount('retailers');
            $query = Utility::prepareSearchQuery($query, $request, new Pjp());
            $query->orderBy('id', 'desc');
            $pjps = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title' => 'PJPs',
                'sub-title' => 'PJP listing fetched successfully',
                'success' => true,
                'data' => $pjps,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/pjps",
     *     tags={"PJP"},
     *     summary="Create a new PJP",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'franchise_id' => 'nullable|exists:franchises,id',
            'day_of_week' => 'required|integer|between:1,7',
            'name' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        // Check if PJP already exists for this employee on this day
        $existing = Pjp::where('employee_id', $attributes['employee_id'])
            ->where('day_of_week', $attributes['day_of_week'])
            ->where('company_id', $request->company->id)
            ->where('is_deleted', false)
            ->first();

        if ($existing) {
            return response()->json([
                'title' => 'PJP',
                'sub-title' => 'A PJP already exists for this employee on ' . Pjp::getDayName($attributes['day_of_week']),
                'success' => false,
            ], 400);
        }

        $attributes['company_id'] = $request->company->id;
        $attributes['is_active'] = $attributes['is_active'] ?? true;

        try {
            $pjp = Pjp::create($attributes);

            // Notify the assigned employee about PJP
            $dayName = Pjp::getDayName($attributes['day_of_week']);
            Notification::send(
                $attributes['employee_id'],
                'New PJP Assigned: ' . $dayName,
                'A new Permanent Journey Plan has been assigned to you for ' . $dayName . '.',
                'pjp',
                ['pjp_id' => $pjp->id, 'day_of_week' => $attributes['day_of_week'], 'action' => 'assigned'],
                $request->company->id
            );

            return response()->json([
                'title' => 'PJP',
                'sub-title' => 'PJP created successfully',
                'success' => true,
                'data' => $pjp->load(['employee:id,first_name,last_name', 'franchise:id,name']),
            ], 200);
        } catch (QueryException $e) {
            // Handle duplicate entry error (MySQL error code 1062)
            if ($e->errorInfo[1] == 1062) {
                $dayName = Pjp::getDayName($attributes['day_of_week']);
                return response()->json([
                    'title' => 'PJP',
                    'sub-title' => "A PJP already exists for this employee on {$dayName}. Please edit the existing PJP or delete it first.",
                    'success' => false,
                    'message' => "A PJP already exists for this employee on {$dayName}. Please edit the existing PJP or delete it first.",
                ], 400);
            }
            throw $e;
        }
    }

    /**
     * @OA\Get(
     *     path="/api/pjps/{id}",
     *     tags={"PJP"},
     *     summary="Get a single PJP with retailers",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Request $request)
    {
        $pjp = Pjp::with([
            'employee:id,first_name,last_name,email,phone',
            'franchise:id,name,code',
            'retailers' => function ($q) {
                $q->select('retailers.id', 'retailers.name', 'retailers.shop_name', 'retailers.phone', 'retailers.address', 'retailers.latitude', 'retailers.longitude');
            }
        ])
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$pjp) {
            return response()->json([
                'title' => 'PJP',
                'sub-title' => 'PJP not found',
                'success' => false,
            ], 404);
        }

        return response()->json([
            'title' => 'PJP',
            'sub-title' => 'PJP fetched successfully',
            'success' => true,
            'data' => $pjp,
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/pjps/{id}",
     *     tags={"PJP"},
     *     summary="Update a PJP",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(Request $request)
    {
        $pjp = Pjp::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$pjp) {
            return response()->json([
                'title' => 'PJP',
                'sub-title' => 'PJP not found',
                'success' => false,
            ], 404);
        }

        $attributes = $request->validate([
            'franchise_id' => 'nullable|exists:franchises,id',
            'name' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $pjp->update($attributes);

        // Notify employee about PJP update
        $dayName = Pjp::getDayName($pjp->day_of_week);
        Notification::send(
            $pjp->employee_id,
            'PJP Updated: ' . $dayName,
            'Your journey plan for ' . $dayName . ' has been updated.',
            'pjp',
            ['pjp_id' => $pjp->id, 'action' => 'updated'],
            $request->company->id
        );

        return response()->json([
            'title' => 'PJP',
            'sub-title' => 'PJP updated successfully',
            'success' => true,
            'data' => $pjp->fresh(['employee:id,first_name,last_name', 'franchise:id,name']),
        ], 200);
    }

    /**
     * Soft delete a PJP
     */
    public function clear(Request $request)
    {
        $pjp = Pjp::find($request->id);

        if (!$pjp) {
            return response()->json([
                'title' => 'PJP',
                'sub-title' => 'PJP not found',
                'success' => false,
            ], 404);
        }

        // Hard delete any existing soft-deleted PJP for the same employee/day
        // This prevents unique constraint violation when soft-deleting
        Pjp::where('employee_id', $pjp->employee_id)
            ->where('day_of_week', $pjp->day_of_week)
            ->where('company_id', $pjp->company_id)
            ->where('is_deleted', true)
            ->where('id', '!=', $pjp->id)
            ->delete();

        $pjp->update(['is_deleted' => true]);

        return response()->json([
            'title' => 'PJP',
            'sub-title' => 'PJP deleted successfully',
            'success' => true,
            'data' => $pjp,
        ], 200);
    }

    /**
     * Restore soft-deleted PJP
     */
    public function restore(Request $request)
    {
        $pjp = Pjp::find($request->id);

        if (!$pjp) {
            return response()->json([
                'title' => 'PJP',
                'sub-title' => 'PJP not found',
                'success' => false,
            ], 404);
        }

        $pjp->update(['is_deleted' => false]);

        return response()->json([
            'title' => 'PJP',
            'sub-title' => 'PJP restored successfully',
            'success' => true,
            'data' => $pjp,
        ], 200);
    }

    /**
     * Hard delete
     */
    public function destroy(Request $request)
    {
        $pjp = Pjp::find($request->id);

        if ($pjp) {
            // Delete related retailers first
            PjpRetailer::where('pjp_id', $pjp->id)->delete();
            $pjp->delete();
        }

        return response()->json([
            'title' => 'PJP',
            'sub-title' => 'PJP permanently deleted',
            'success' => true,
        ], 200);
    }

    /**
     * Get PJPs for current user (employee view)
     */
    public function myPjps(Request $request)
    {
        $pjps = Pjp::with(['franchise:id,name', 'retailers' => function ($q) {
            $q->select('retailers.id', 'retailers.name', 'retailers.shop_name', 'retailers.phone', 'retailers.address', 'retailers.latitude', 'retailers.longitude');
        }])
            ->where('employee_id', $request->user()->id)
            ->where('company_id', $request->company->id)
            ->where('is_deleted', false)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->get();

        return response()->json([
            'title' => 'My PJPs',
            'sub-title' => 'PJPs fetched successfully',
            'success' => true,
            'data' => $pjps,
        ], 200);
    }

    /**
     * Get today's PJP for current user
     */
    public function todayPjp(Request $request)
    {
        $dayOfWeek = Carbon::now()->dayOfWeekIso; // 1 (Monday) to 7 (Sunday)
        $today = Carbon::today()->toDateString();
        $pjp = Pjp::with(['franchise:id,name', 'retailers' => function ($q) use ($today) {
            $q->select('retailers.id', 'retailers.name', 'retailers.shop_name', 'retailers.phone', 'retailers.address', 'retailers.latitude', 'retailers.longitude', 'retailers.is_flagged')
                ->where(function ($query) use ($today) {
                    $query->whereNull('pjp_retailers.effective_from')
                        ->orWhere('pjp_retailers.effective_from', '<=', $today);
                });
        }])
            ->where('employee_id', $request->user()->id)
            ->where('company_id', $request->company->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_deleted', false)
            ->where('is_active', true)
            ->first();
        // Check for approved changes for today
        $approvedChanges = [];
        if ($pjp) {
            $approvedChanges = PjpChange::where('pjp_id', $pjp->id)
                ->where('date', Carbon::today())
                ->where('status', 'approved')
                ->get();
        }

        return response()->json([
            'title' => 'Today\'s PJP',
            'sub-title' => 'Today\'s PJP fetched successfully',
            'success' => true,
            'data' => [
                'pjp' => $pjp,
                'approved_changes' => $approvedChanges,
                'day_name' => Carbon::now()->format('l'),
            ],
        ], 200);
    }

    /**
     * Get PJP for a specific date
     */
    public function getPjpByDate(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'employee_id' => 'nullable|exists:users,id',
        ]);

        $date = Carbon::parse($request->date);
        $dayOfWeek = $date->dayOfWeekIso;
        $employeeId = $request->employee_id ?? $request->user()->id;

        $dateString = $date->toDateString();
        $pjp = Pjp::with(['franchise:id,name', 'retailers' => function ($q) use ($dateString) {
            $q->select('retailers.id', 'retailers.name', 'retailers.shop_name', 'retailers.phone', 'retailers.address', 'retailers.latitude', 'retailers.longitude')
                ->where(function ($query) use ($dateString) {
                    $query->whereNull('pjp_retailers.effective_from')
                        ->orWhere('pjp_retailers.effective_from', '<=', $dateString);
                });
        }])
            ->where('employee_id', $employeeId)
            ->where('company_id', $request->company->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_deleted', false)
            ->where('is_active', true)
            ->first();

        return response()->json([
            'title' => 'PJP',
            'sub-title' => 'PJP for ' . $date->format('d M Y') . ' fetched',
            'success' => true,
            'data' => $pjp,
        ], 200);
    }

    /**
     * Assign retailers to a PJP
     */
    public function assignRetailers(Request $request)
    {
        $request->validate([
            'pjp_id' => 'required|exists:pjps,id',
            'retailers' => 'required|array',
            'retailers.*.retailer_id' => 'required|exists:retailers,id',
            'retailers.*.sequence' => 'required|integer|min:1',
            'retailers.*.planned_visit_time' => 'nullable|date_format:H:i',
            'retailers.*.expected_duration' => 'nullable|integer|min:1',
        ]);

        $pjp = Pjp::where('id', $request->pjp_id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$pjp) {
            return response()->json([
                'title' => 'PJP',
                'sub-title' => 'PJP not found',
                'success' => false,
            ], 404);
        }

        // Remove existing retailers
        PjpRetailer::where('pjp_id', $pjp->id)->delete();

        // Add new retailers
        foreach ($request->retailers as $retailerData) {
            PjpRetailer::create([
                'pjp_id' => $pjp->id,
                'retailer_id' => $retailerData['retailer_id'],
                'sequence' => $retailerData['sequence'],
                'planned_visit_time' => $retailerData['planned_visit_time'] ?? null,
                'expected_duration' => $retailerData['expected_duration'] ?? null,
                'is_active' => true,
            ]);
        }

        // Notify employee about retailer assignment
        $dayName = Pjp::getDayName($pjp->day_of_week);
        $retailerCount = count($request->retailers);
        Notification::send(
            $pjp->employee_id,
            'PJP Retailers Updated: ' . $dayName,
            $retailerCount . ' retailer(s) assigned to your ' . $dayName . ' journey plan.',
            'pjp',
            ['pjp_id' => $pjp->id, 'action' => 'retailers_assigned', 'count' => $retailerCount],
            $request->company->id
        );

        return response()->json([
            'title' => 'PJP',
            'sub-title' => 'Retailers assigned successfully',
            'success' => true,
            'data' => $pjp->fresh(['retailers']),
        ], 200);
    }

    /**
     * Add a single retailer to PJP
     */
    public function addRetailer(Request $request)
    {
        $request->validate([
            'pjp_id' => 'required|exists:pjps,id',
            'retailer_id' => 'required|exists:retailers,id',
            'sequence' => 'nullable|integer|min:1',
            'planned_visit_time' => 'nullable|date_format:H:i',
        ]);

        $pjp = Pjp::where('id', $request->pjp_id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$pjp) {
            return response()->json([
                'title' => 'PJP',
                'sub-title' => 'PJP not found',
                'success' => false,
            ], 404);
        }

        // Check if retailer already exists in this PJP
        $exists = PjpRetailer::where('pjp_id', $pjp->id)
            ->where('retailer_id', $request->retailer_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'title' => 'PJP',
                'sub-title' => 'Retailer already exists in this PJP',
                'success' => false,
            ], 400);
        }

        // Get next sequence if not provided
        $sequence = $request->sequence ?? (PjpRetailer::where('pjp_id', $pjp->id)->max('sequence') + 1);

        $pjpRetailer = PjpRetailer::create([
            'pjp_id' => $pjp->id,
            'retailer_id' => $request->retailer_id,
            'sequence' => $sequence,
            'planned_visit_time' => $request->planned_visit_time,
            'is_active' => true,
        ]);

        return response()->json([
            'title' => 'PJP',
            'sub-title' => 'Retailer added to PJP',
            'success' => true,
            'data' => $pjpRetailer->load('retailer'),
        ], 200);
    }

    /**
     * Remove a retailer from PJP
     */
    public function removeRetailer(Request $request)
    {
        $request->validate([
            'pjp_id' => 'required|exists:pjps,id',
            'retailer_id' => 'required|exists:retailers,id',
        ]);

        $deleted = PjpRetailer::where('pjp_id', $request->pjp_id)
            ->where('retailer_id', $request->retailer_id)
            ->delete();

        return response()->json([
            'title' => 'PJP',
            'sub-title' => $deleted ? 'Retailer removed from PJP' : 'Retailer not found in PJP',
            'success' => (bool) $deleted,
        ], 200);
    }

    /**
     * Reorder retailers in a PJP
     */
    public function reorderRetailers(Request $request)
    {
        $request->validate([
            'pjp_id' => 'required|exists:pjps,id',
            'order' => 'required|array',
            'order.*.retailer_id' => 'required|exists:retailers,id',
            'order.*.sequence' => 'required|integer|min:1',
        ]);

        foreach ($request->order as $item) {
            PjpRetailer::where('pjp_id', $request->pjp_id)
                ->where('retailer_id', $item['retailer_id'])
                ->update(['sequence' => $item['sequence']]);
        }

        return response()->json([
            'title' => 'PJP',
            'sub-title' => 'Retailers reordered successfully',
            'success' => true,
        ], 200);
    }

    /**
     * Get days reference
     */
    public function getDays()
    {
        return response()->json([
            'title' => 'Days',
            'sub-title' => 'Days of week reference',
            'success' => true,
            'data' => Pjp::getDaysArray(),
        ], 200);
    }
}
