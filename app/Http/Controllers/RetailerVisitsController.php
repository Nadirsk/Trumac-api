<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Retailer;
use App\Models\RetailerVisit;
use Illuminate\Http\Request;

class RetailerVisitsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'checkOut']);
    }

    /**
     * Get all retailer visits (with filters)
     */
    public function index(Request $request)
    {
        try {
            $query = RetailerVisit::query()->with(['user', 'retailer', 'pjp']);
            $query = Utility::prepareSearchQuery($query, $request, new RetailerVisit());
            $query->orderBy('id', 'desc');
            return Utility::getSearchRequestQueryResults($request, $query);
        } catch (\Exception $e) {
            return response()->json([
                'title' => 'Retailer Visits',
                'sub-title' => 'Error Fetching Retailer Visits',
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get today's visits for authenticated user
     */
    public function myTodayVisits(Request $request)
    {
        $visits = RetailerVisit::where('user_id', auth()->id())
            ->where('visit_date', today())
            ->where('is_deleted', false)
            ->with(['retailer', 'pjp'])
            ->get();

        return response()->json([
            'title' => 'Today\'s Visits',
            'sub-title' => 'Visits Fetched Successfully',
            'success' => true,
            'data' => $visits,
        ], 200);
    }

    /**
     * Store a new retailer visit (check-in)
     */
    public function store(Request $request)
    {
        try {
            $attributes = $request->validate([
                'retailer_id' => 'required|exists:retailers,id',
                'pjp_id' => 'nullable|exists:pjps,id',
                'visit_type' => 'required|in:planned,unplanned',
                'visit_date' => 'required|date',
                'check_in_time' => 'required',
                'check_in_latitude' => 'nullable|numeric',
                'check_in_longitude' => 'nullable|numeric',
                'check_in_accuracy' => 'nullable|numeric|min:0',
                'check_in_distance' => 'nullable|numeric|min:0',
                'device_id' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'title' => 'Validation Error',
                'sub-title' => 'Invalid data provided',
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        }

        // GPS validation - check accuracy
        if (isset($attributes['check_in_accuracy']) && $attributes['check_in_accuracy'] > 100) {
            return response()->json([
                'title' => 'GPS Error',
                'sub-title' => 'GPS accuracy is too low. Please wait for better signal.',
                'success' => false,
                'errors' => ['check_in_accuracy' => ['GPS accuracy must be within 100 meters']],
            ], 422);
        }

        // GPS validation - distance check for planned visits
        if ($attributes['visit_type'] === 'planned') {
            $retailer = Retailer::find($attributes['retailer_id']);

            if ($retailer && $retailer->latitude && $retailer->longitude
                && isset($attributes['check_in_latitude']) && isset($attributes['check_in_longitude'])) {
                $distance = $this->calculateDistance(
                    $attributes['check_in_latitude'],
                    $attributes['check_in_longitude'],
                    $retailer->latitude,
                    $retailer->longitude
                );

                $attributes['check_in_distance'] = round($distance, 2);

                if ($distance > 200) {
                    return response()->json([
                        'title' => 'Location Error',
                        'sub-title' => 'You are too far from the retailer location (' . round($distance) . 'm away). Please move closer.',
                        'success' => false,
                        'errors' => ['check_in_distance' => ['Must be within 200 meters of retailer. Current distance: ' . round($distance) . 'm']],
                    ], 422);
                }
            }
        }

        // For unplanned visits, calculate distance if coordinates available
        if ($attributes['visit_type'] === 'unplanned' && !isset($attributes['check_in_distance'])) {
            $retailer = Retailer::find($attributes['retailer_id']);
            if ($retailer && $retailer->latitude && $retailer->longitude
                && isset($attributes['check_in_latitude']) && isset($attributes['check_in_longitude'])) {
                $attributes['check_in_distance'] = round($this->calculateDistance(
                    $attributes['check_in_latitude'],
                    $attributes['check_in_longitude'],
                    $retailer->latitude,
                    $retailer->longitude
                ), 2);
            }
        }

        // Check if visit already exists for today
        $existingVisit = RetailerVisit::where('user_id', auth()->id())
            ->where('retailer_id', $attributes['retailer_id'])
            ->where('visit_date', $attributes['visit_date'])
            ->where('is_deleted', false)
            ->first();

        if ($existingVisit) {
            return response()->json([
                'title' => 'Retailer Visit',
                'sub-title' => 'Visit Already Recorded',
                'success' => true,
                'data' => $existingVisit->load(['retailer', 'pjp']),
                'message' => 'Visit already exists for this retailer today',
            ], 200);
        }

        // Create via company relationship for multi-tenancy
        $visit = $request->company->retailerVisits()->create([
            'user_id' => auth()->id(),
            'retailer_id' => $attributes['retailer_id'],
            'pjp_id' => $attributes['pjp_id'] ?? null,
            'visit_type' => $attributes['visit_type'],
            'visit_date' => $attributes['visit_date'],
            'check_in_time' => $attributes['check_in_time'],
            'check_in_latitude' => $attributes['check_in_latitude'] ?? null,
            'check_in_longitude' => $attributes['check_in_longitude'] ?? null,
            'check_in_accuracy' => $attributes['check_in_accuracy'] ?? null,
            'check_in_distance' => $attributes['check_in_distance'] ?? null,
            'device_id' => $attributes['device_id'] ?? null,
            'notes' => $attributes['notes'] ?? null,
        ]);

        return response()->json([
            'title' => 'Retailer Visit',
            'sub-title' => 'Check-in Recorded Successfully',
            'success' => true,
            'data' => $visit->load(['retailer', 'pjp']),
        ], 200);
    }

    /**
     * Update visit with check-out time
     */
    public function checkOut(Request $request)
    {
        $attributes = $request->validate([
            'check_out_time' => 'required',
            'check_out_latitude' => 'nullable|numeric',
            'check_out_longitude' => 'nullable|numeric',
            'check_out_accuracy' => 'nullable|numeric|min:0',
            'check_out_distance' => 'nullable|numeric|min:0',
        ]);

        $visit = RetailerVisit::find($request->id);

        if (!$visit) {
            return response()->json([
                'title' => 'Retailer Visit',
                'sub-title' => 'Visit Not Found',
                'success' => false,
            ], 404);
        }

        if ($visit->check_out_time) {
            return response()->json([
                'title' => 'Retailer Visit',
                'sub-title' => 'Already Checked Out',
                'success' => false,
                'data' => $visit,
            ], 400);
        }

        // Calculate check-out distance from retailer
        if (!isset($attributes['check_out_distance'])
            && isset($attributes['check_out_latitude']) && isset($attributes['check_out_longitude'])) {
            $retailer = $visit->retailer;
            if ($retailer && $retailer->latitude && $retailer->longitude) {
                $attributes['check_out_distance'] = round($this->calculateDistance(
                    $attributes['check_out_latitude'],
                    $attributes['check_out_longitude'],
                    $retailer->latitude,
                    $retailer->longitude
                ), 2);
            }
        }

        $visit->update([
            'check_out_time' => $attributes['check_out_time'],
            'check_out_latitude' => $attributes['check_out_latitude'] ?? null,
            'check_out_longitude' => $attributes['check_out_longitude'] ?? null,
            'check_out_accuracy' => $attributes['check_out_accuracy'] ?? null,
            'check_out_distance' => $attributes['check_out_distance'] ?? null,
        ]);

        return response()->json([
            'title' => 'Retailer Visit',
            'sub-title' => 'Check-out Recorded Successfully',
            'success' => true,
            'data' => $visit->load(['retailer', 'pjp']),
        ], 200);
    }

    /**
     * Show single visit
     */
    public function show(Request $request)
    {
        $visit = RetailerVisit::find($request->id);

        if (!$visit) {
            return response()->json([
                'title' => 'Retailer Visit',
                'sub-title' => 'Visit Not Found',
                'success' => false,
            ], 404);
        }

        return response()->json([
            'title' => 'Retailer Visit',
            'sub-title' => 'Visit Fetched Successfully',
            'success' => true,
            'data' => $visit->load(['user', 'retailer', 'pjp']),
        ], 200);
    }

    /**
     * Calculate distance between two GPS coordinates using Haversine formula
     * Returns distance in meters
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
