<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\JourneyPlan;
use App\Models\JourneyStop;
use App\Models\Notification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class JourneyPlansController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'start', 'complete', 'cancel', 'addStop', 'updateStop', 'arriveStop', 'completeStop', 'skipStop']);
    }

    /**
     * Get all journey plans
     */
    public function index(Request $request)
    {
        try {
            $query = JourneyPlan::with(['driver:id,first_name,last_name,phone', 'stops']);
            $query = Utility::prepareSearchQuery($query, $request, new JourneyPlan());
            $query->orderBy('id', 'desc');
            $journeyPlans = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title' => 'Journey Plans',
                'sub-title' => 'Journey plans fetched successfully',
                'success' => true,
                'data' => $journeyPlans,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * Create a new journey plan
     */
    public function store(Request $request)
    {
        $request->validate([
            'driver_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'vehicle_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:500',
            'stops' => 'nullable|array',
            'stops.*.stop_type' => 'required_with:stops|string',
            'stops.*.reference_type' => 'nullable|string',
            'stops.*.reference_id' => 'nullable|integer',
            'stops.*.name' => 'required_with:stops|string|max:255',
            'stops.*.address' => 'nullable|string',
            'stops.*.latitude' => 'nullable|numeric',
            'stops.*.longitude' => 'nullable|numeric',
            'stops.*.planned_time' => 'nullable|date_format:H:i',
        ]);

        // Reuse existing journey plan for same driver + date if still active
        $journeyPlan = JourneyPlan::where('driver_id', $request->driver_id)
            ->where('company_id', $request->company->id)
            ->whereDate('date', $request->date)
            ->where('is_deleted', false)
            ->whereIn('status', [JourneyPlan::STATUS_PLANNED, JourneyPlan::STATUS_STARTED])
            ->first();

        if (!$journeyPlan) {
            $journeyPlan = JourneyPlan::create([
                'driver_id' => $request->driver_id,
                'date' => $request->date,
                'status' => JourneyPlan::STATUS_PLANNED,
                'vehicle_id' => $request->vehicle_id,
                'notes' => $request->notes,
                'company_id' => $request->company->id,
            ]);
        }

        // Append stops after any existing stops
        if ($request->filled('stops')) {
            $nextSequence = ($journeyPlan->stops()->max('sequence') ?? 0) + 1;
            foreach ($request->stops as $index => $stop) {
                $journeyPlan->stops()->create([
                    'stop_type' => $stop['stop_type'],
                    'reference_type' => $stop['reference_type'] ?? null,
                    'reference_id' => $stop['reference_id'] ?? null,
                    'sequence' => $nextSequence + $index,
                    'name' => $stop['name'],
                    'address' => $stop['address'] ?? null,
                    'latitude' => $stop['latitude'] ?? null,
                    'longitude' => $stop['longitude'] ?? null,
                    'planned_time' => $stop['planned_time'] ?? null,
                    'status' => JourneyStop::STATUS_PENDING,
                ]);
            }
        }

        // Notify driver about journey plan
        Notification::send(
            $request->driver_id,
            'Journey Plan Assigned: ' . $request->date,
            'A journey plan has been assigned to you for ' . \Carbon\Carbon::parse($request->date)->format('d M Y') . '.',
            'journey_plan',
            ['journey_plan_id' => $journeyPlan->id, 'action' => 'assigned'],
            $request->company->id
        );

        return response()->json([
            'title' => 'Journey Plan',
            'sub-title' => 'Journey plan created',
            'success' => true,
            'data' => $journeyPlan->load(['driver:id,first_name,last_name', 'stops']),
        ], 200);
    }

    /**
     * Get a single journey plan
     */
    public function show(Request $request)
    {
        $journeyPlan = JourneyPlan::with([
            'driver:id,first_name,last_name,phone',
            'stops',
        ])
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$journeyPlan) {
            return response()->json([
                'title' => 'Journey Plan',
                'sub-title' => 'Journey plan not found',
                'success' => false,
            ], 404);
        }

        return response()->json([
            'title' => 'Journey Plan',
            'sub-title' => 'Journey plan fetched successfully',
            'success' => true,
            'data' => $journeyPlan,
        ], 200);
    }

    /**
     * Update a journey plan
     */
    public function update(Request $request)
    {
        $journeyPlan = JourneyPlan::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$journeyPlan) {
            return response()->json([
                'title' => 'Journey Plan',
                'sub-title' => 'Journey plan not found',
                'success' => false,
            ], 404);
        }

        if ($journeyPlan->status !== JourneyPlan::STATUS_PLANNED) {
            return response()->json([
                'title' => 'Journey Plan',
                'sub-title' => 'Can only update planned journeys',
                'success' => false,
            ], 400);
        }

        $request->validate([
            'driver_id' => 'sometimes|exists:users,id',
            'date' => 'sometimes|date',
            'vehicle_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:500',
        ]);

        $journeyPlan->update($request->only(['driver_id', 'date', 'vehicle_id', 'notes']));

        return response()->json([
            'title' => 'Journey Plan',
            'sub-title' => 'Journey plan updated',
            'success' => true,
            'data' => $journeyPlan->fresh(['driver:id,first_name,last_name', 'stops']),
        ], 200);
    }

    /**
     * Start a journey
     */
    public function start(Request $request)
    {
        $request->validate([
            'odometer' => 'required|numeric|min:0',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $journeyPlan = JourneyPlan::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$journeyPlan) {
            return response()->json([
                'title' => 'Journey Plan',
                'sub-title' => 'Journey plan not found',
                'success' => false,
            ], 404);
        }

        if (!$journeyPlan->canStart()) {
            return response()->json([
                'title' => 'Journey Plan',
                'sub-title' => 'Journey cannot be started',
                'success' => false,
            ], 400);
        }

        $journeyPlan->start($request->odometer, $request->latitude, $request->longitude);

        return response()->json([
            'title' => 'Journey Plan',
            'sub-title' => 'Journey started',
            'success' => true,
            'data' => $journeyPlan->fresh(['driver:id,first_name,last_name', 'stops']),
        ], 200);
    }

    /**
     * Complete a journey
     */
    public function complete(Request $request)
    {
        $request->validate([
            'odometer' => 'required|numeric|min:0',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $journeyPlan = JourneyPlan::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$journeyPlan) {
            return response()->json([
                'title' => 'Journey Plan',
                'sub-title' => 'Journey plan not found',
                'success' => false,
            ], 404);
        }

        if (!$journeyPlan->canComplete()) {
            return response()->json([
                'title' => 'Journey Plan',
                'sub-title' => 'Journey cannot be completed',
                'success' => false,
            ], 400);
        }

        $journeyPlan->complete($request->odometer, $request->latitude, $request->longitude);

        return response()->json([
            'title' => 'Journey Plan',
            'sub-title' => 'Journey completed',
            'success' => true,
            'data' => $journeyPlan->fresh(['driver:id,first_name,last_name', 'stops']),
        ], 200);
    }

    /**
     * Cancel a journey
     */
    public function cancel(Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $journeyPlan = JourneyPlan::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$journeyPlan) {
            return response()->json([
                'title' => 'Journey Plan',
                'sub-title' => 'Journey plan not found',
                'success' => false,
            ], 404);
        }

        if ($journeyPlan->status === JourneyPlan::STATUS_COMPLETED) {
            return response()->json([
                'title' => 'Journey Plan',
                'sub-title' => 'Cannot cancel completed journey',
                'success' => false,
            ], 400);
        }

        $journeyPlan->update([
            'status' => JourneyPlan::STATUS_CANCELLED,
            'notes' => $request->reason,
        ]);

        return response()->json([
            'title' => 'Journey Plan',
            'sub-title' => 'Journey cancelled',
            'success' => true,
            'data' => $journeyPlan,
        ], 200);
    }

    /**
     * Add a stop to journey
     */
    public function addStop(Request $request)
    {
        $request->validate([
            'stop_type' => 'required|string',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'planned_time' => 'nullable|date_format:H:i',
            'sequence' => 'nullable|integer',
        ]);

        $journeyPlan = JourneyPlan::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$journeyPlan) {
            return response()->json([
                'title' => 'Journey Plan',
                'sub-title' => 'Journey plan not found',
                'success' => false,
            ], 404);
        }

        // Get sequence
        $sequence = $request->sequence ?? ($journeyPlan->stops()->max('sequence') + 1);

        $stop = $journeyPlan->stops()->create([
            'stop_type' => $request->stop_type,
            'reference_type' => $request->reference_type,
            'reference_id' => $request->reference_id,
            'sequence' => $sequence,
            'name' => $request->name,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'planned_time' => $request->planned_time,
            'status' => JourneyStop::STATUS_PENDING,
        ]);

        return response()->json([
            'title' => 'Journey Stop',
            'sub-title' => 'Stop added',
            'success' => true,
            'data' => $stop,
        ], 200);
    }

    /**
     * Decrypt an encrypted ID parameter
     */
    private function decryptParam($encryptedData)
    {
        if (!$encryptedData) {
            return null;
        }
        $decodedEncryptedData = str_replace(['-', '_'], ['+', '/'], urldecode($encryptedData));
        $secretKey = '12345678123456781234567812345678';
        $iv = 'Ef7ix7ETPgghl3vP';
        $decryptedData = openssl_decrypt(base64_decode($decodedEncryptedData), 'AES-256-CBC', $secretKey, OPENSSL_RAW_DATA, $iv);
        return $decryptedData !== false ? $decryptedData : null;
    }

    /**
     * Arrive at a stop
     */
    public function arriveStop(Request $request, $journeyId, $stopId)
    {
        $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $journeyPlan = JourneyPlan::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$journeyPlan) {
            return response()->json([
                'title' => 'Journey Plan',
                'sub-title' => 'Journey plan not found',
                'success' => false,
            ], 404);
        }

        // Decrypt the stop ID
        $decryptedStopId = $this->decryptParam($stopId);
        $stop = $journeyPlan->stops()->find($decryptedStopId);
        if (!$stop) {
            return response()->json([
                'title' => 'Journey Stop',
                'sub-title' => 'Stop not found',
                'success' => false,
            ], 404);
        }

        if (!$stop->canArrive()) {
            return response()->json([
                'title' => 'Journey Stop',
                'sub-title' => 'Cannot arrive at this stop',
                'success' => false,
            ], 400);
        }

        $stop->arrive($request->latitude, $request->longitude);

        return response()->json([
            'title' => 'Journey Stop',
            'sub-title' => 'Arrived at stop',
            'success' => true,
            'data' => $stop,
        ], 200);
    }

    /**
     * Complete a stop with signature
     */
    public function completeStop(Request $request, $journeyId, $stopId)
    {
        $request->validate([
            'signature' => 'nullable|string', // Base64 signature
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string|max:500',
        ]);

        $journeyPlan = JourneyPlan::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$journeyPlan) {
            return response()->json([
                'title' => 'Journey Plan',
                'sub-title' => 'Journey plan not found',
                'success' => false,
            ], 404);
        }

        // Decrypt the stop ID
        $decryptedStopId = $this->decryptParam($stopId);
        $stop = $journeyPlan->stops()->find($decryptedStopId);
        if (!$stop) {
            return response()->json([
                'title' => 'Journey Stop',
                'sub-title' => 'Stop not found',
                'success' => false,
            ], 404);
        }

        if (!$stop->canComplete()) {
            return response()->json([
                'title' => 'Journey Stop',
                'sub-title' => 'Cannot complete this stop',
                'success' => false,
            ], 400);
        }

        // Save signature if provided
        $signaturePath = null;
        if ($request->filled('signature')) {
            $signatureData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->signature));
            $signaturePath = 'signatures/journey/' . $journeyPlan->id . '_' . $stop->id . '_' . time() . '.png';
            Storage::disk('public')->put($signaturePath, $signatureData);
        }

        $stop->complete($signaturePath, $request->latitude, $request->longitude, $request->notes);

        return response()->json([
            'title' => 'Journey Stop',
            'sub-title' => 'Stop completed',
            'success' => true,
            'data' => $stop,
        ], 200);
    }

    /**
     * Skip a stop
     */
    public function skipStop(Request $request, $journeyId, $stopId)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $journeyPlan = JourneyPlan::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$journeyPlan) {
            return response()->json([
                'title' => 'Journey Plan',
                'sub-title' => 'Journey plan not found',
                'success' => false,
            ], 404);
        }

        // Decrypt the stop ID
        $decryptedStopId = $this->decryptParam($stopId);
        $stop = $journeyPlan->stops()->find($decryptedStopId);
        if (!$stop) {
            return response()->json([
                'title' => 'Journey Stop',
                'sub-title' => 'Stop not found',
                'success' => false,
            ], 404);
        }

        if ($stop->status === JourneyStop::STATUS_COMPLETED) {
            return response()->json([
                'title' => 'Journey Stop',
                'sub-title' => 'Cannot skip completed stop',
                'success' => false,
            ], 400);
        }

        $stop->skip($request->reason);

        return response()->json([
            'title' => 'Journey Stop',
            'sub-title' => 'Stop skipped',
            'success' => true,
            'data' => $stop,
        ], 200);
    }

    /**
     * Get today's journey for current user (driver)
     */
    public function todayJourney(Request $request)
    {
        $journeyPlans = JourneyPlan::with(['stops'])
            ->where('company_id', $request->company->id)
            ->where('driver_id', $request->user()->id)
            ->whereDate('date', today())
            ->where('is_deleted', false)
            ->orderByRaw("FIELD(status, 'started', 'planned', 'completed', 'cancelled')")
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'title' => 'Today\'s Journey',
            'sub-title' => $journeyPlans->isNotEmpty() ? 'Journey fetched' : 'No journey for today',
            'success' => true,
            'data' => $journeyPlans,
        ], 200);
    }

    /**
     * Get my journey history
     */
    public function myJourneys(Request $request)
    {
        $query = JourneyPlan::with(['stops'])
            ->where('company_id', $request->company->id)
            ->where('driver_id', $request->user()->id)
            ->where('is_deleted', false)
            ->orderBy('date', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $items = $query->limit(50)->get();

        return response()->json([
            'title' => 'My Journeys',
            'sub-title' => 'Journeys fetched',
            'success' => true,
            'count' => $items->count(),
            'data' => $items,
        ], 200);
    }

    /**
     * Get journey summary/statistics
     */
    public function summary(Request $request)
    {
        $query = JourneyPlan::where('company_id', $request->company->id)
            ->where('is_deleted', false);

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('date', [$request->from_date, $request->to_date]);
        } else {
            $query->whereMonth('date', now()->month);
        }

        $completedJourneys = (clone $query)->where('status', JourneyPlan::STATUS_COMPLETED)->get();

        $totalDistance = $completedJourneys->sum(function ($j) {
            return $j->end_odometer && $j->start_odometer ? $j->end_odometer - $j->start_odometer : 0;
        });

        $totalStops = JourneyStop::whereIn('journey_plan_id', $completedJourneys->pluck('id'))
            ->where('status', JourneyStop::STATUS_COMPLETED)
            ->count();

        $summary = [
            'total_journeys' => $query->count(),
            'completed_journeys' => $completedJourneys->count(),
            'planned_journeys' => (clone $query)->where('status', JourneyPlan::STATUS_PLANNED)->count(),
            'cancelled_journeys' => (clone $query)->where('status', JourneyPlan::STATUS_CANCELLED)->count(),
            'total_distance_km' => round($totalDistance, 2),
            'total_stops_completed' => $totalStops,
        ];

        return response()->json([
            'title' => 'Journey Summary',
            'sub-title' => 'Summary fetched',
            'success' => true,
            'data' => $summary,
        ], 200);
    }

    /**
     * Reorder stops
     */
    public function reorderStops(Request $request)
    {
        $request->validate([
            'stops' => 'required|array',
            'stops.*.id' => 'required|integer',
            'stops.*.sequence' => 'required|integer',
        ]);

        $journeyPlan = JourneyPlan::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$journeyPlan) {
            return response()->json([
                'title' => 'Journey Plan',
                'sub-title' => 'Journey plan not found',
                'success' => false,
            ], 404);
        }

        foreach ($request->stops as $stopData) {
            JourneyStop::where('id', $stopData['id'])
                ->where('journey_plan_id', $journeyPlan->id)
                ->update(['sequence' => $stopData['sequence']]);
        }

        return response()->json([
            'title' => 'Journey Stops',
            'sub-title' => 'Stops reordered',
            'success' => true,
            'data' => $journeyPlan->fresh(['stops']),
        ], 200);
    }
}
