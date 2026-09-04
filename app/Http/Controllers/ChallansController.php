<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Challan;
use App\Models\ChallanItem;
use App\Models\Notification;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;

class ChallansController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'downloadPdf', 'dispatch', 'deliver']);
    }

    /**
     * Get all challans
     */
    public function index(Request $request)
    {
        try {
            $query = Challan::with([
                'items.sku:id,code,name,unit',
                'requisition:id,requisition_number',
                'driver:id,first_name,last_name',
                'creator:id,first_name,last_name',
            ])
                ->withCount('items')
                ->where('is_deleted', false);

            $query = Utility::prepareSearchQuery($query, $request, new Challan());
            $query->orderBy('id', 'desc');
            $challans = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title' => 'Challans',
                'sub-title' => 'Challans fetched successfully',
                'success' => true,
                'data' => $challans,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get a single challan
     */
    public function show(Request $request)
    {
        $challan = Challan::with([
            'items.sku:id,code,name,unit',
            'requisition:id,requisition_number',
            'driver:id,first_name,last_name,phone',
            'creator:id,first_name,last_name',
            'journeyPlan',
        ])
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$challan) {
            return response()->json([
                'title' => 'Challan',
                'sub-title' => 'Challan not found',
                'success' => false,
            ], 404);
        }

        // Load location info
        $challan->from_location = $challan->getFromLocation();
        $challan->to_location = $challan->getToLocation();

        return response()->json([
            'title' => 'Challan',
            'sub-title' => 'Challan fetched successfully',
            'success' => true,
            'data' => $challan,
        ], 200);
    }

    /**
     * Download challan as PDF
     */
    public function downloadPdf(Request $request)
    {
        $challan = Challan::with([
            'items.sku:id,code,name,unit',
            'requisition:id,requisition_number',
            'driver:id,first_name,last_name,phone',
            'creator:id,first_name,last_name',
            'company',
        ])
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$challan) {
            return response()->json([
                'title' => 'Challan',
                'sub-title' => 'Challan not found',
                'success' => false,
            ], 404);
        }

        // Load location info
        $challan->from_location = $challan->getFromLocation();
        $challan->to_location = $challan->getToLocation();

        ini_set('memory_limit', '1G');
        $pdf = Pdf::loadView('pdfs.challan', ['challan' => $challan]);

        return $pdf->download('challan-' . $challan->challan_number . '.pdf');
    }

    /**
     * Mark challan as dispatched (in transit)
     */
    public function dispatch(Request $request)
    {
        $challan = Challan::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$challan) {
            return response()->json([
                'title' => 'Challan',
                'sub-title' => 'Challan not found',
                'success' => false,
            ], 404);
        }

        if ($challan->status !== Challan::STATUS_GENERATED) {
            return response()->json([
                'title' => 'Challan',
                'sub-title' => 'Challan cannot be dispatched',
                'success' => false,
            ], 400);
        }

        $challan->dispatch();

        // Notify driver about dispatch
        if ($challan->driver_id) {
            Notification::send(
                $challan->driver_id,
                'Challan Dispatched: ' . $challan->challan_number,
                'Challan ' . $challan->challan_number . ' is now in transit. Start your delivery.',
                'challan',
                ['challan_id' => $challan->id, 'action' => 'dispatched'],
                $request->company->id
            );
        }

        return response()->json([
            'title' => 'Challan',
            'sub-title' => 'Challan dispatched successfully',
            'success' => true,
            'data' => $challan->fresh(['items.sku:id,code,name,unit', 'driver:id,first_name,last_name']),
        ], 200);
    }

    /**
     * Mark challan as delivered
     */
    public function deliver(Request $request)
    {
        $request->validate([
            'received_by' => 'nullable|string|max:100',
            'receiver_signature' => 'nullable|string',
            'delivery_proof_image' => 'nullable|string',
            'delivery_notes' => 'nullable|string|max:1000',
        ]);

        $challan = Challan::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$challan) {
            return response()->json([
                'title' => 'Challan',
                'sub-title' => 'Challan not found',
                'success' => false,
            ], 404);
        }

        if (!in_array($challan->status, [Challan::STATUS_GENERATED, Challan::STATUS_IN_TRANSIT])) {
            return response()->json([
                'title' => 'Challan',
                'sub-title' => 'Challan cannot be marked as delivered',
                'success' => false,
            ], 400);
        }

        $challan->markDelivered(
            $request->received_by,
            $request->receiver_signature,
            $request->delivery_proof_image,
            $request->delivery_notes
        );

        // Notify destination location users about delivery
        $destRoleMap = ['warehouse' => 5, 'company_godown' => 6, 'franchise' => 7];
        $destRoleId = $destRoleMap[$challan->to_location_type] ?? null;
        if ($destRoleId) {
            $destUsers = User::whereHas('position', fn($q) => $q->where('role_id', $destRoleId))
                ->whereHas('companies', fn($q) => $q->where('companies.id', $request->company->id))
                ->pluck('id')->toArray();

            if (!empty($destUsers)) {
                Notification::sendToMany(
                    $destUsers,
                    'Challan Delivered: ' . $challan->challan_number,
                    'Challan ' . $challan->challan_number . ' has been delivered to your location. Please verify and create GRN.',
                    'challan',
                    ['challan_id' => $challan->id, 'action' => 'delivered'],
                    $request->company->id
                );
            }
        }

        return response()->json([
            'title' => 'Challan',
            'sub-title' => 'Challan delivered successfully',
            'success' => true,
            'data' => $challan->fresh(['items.sku:id,code,name,unit', 'driver:id,first_name,last_name']),
        ], 200);
    }

    /**
     * Get challans for driver (pending deliveries)
     */
    public function myChallans(Request $request)
    {
        $query = Challan::with([
            'items.sku:id,code,name,unit',
            'requisition:id,requisition_number',
        ])
            ->where('company_id', $request->company->id)
            ->where('driver_id', $request->user()->id)
            ->where('is_deleted', false)
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default to pending deliveries
            $query->pendingDelivery();
        }

        $challans = $query->limit(50)->get();

        // Add location info
        $challans->each(function ($challan) {
            $challan->from_location = $challan->getFromLocation();
            $challan->to_location = $challan->getToLocation();
        });

        return response()->json([
            'title' => 'My Challans',
            'sub-title' => 'Challans fetched',
            'success' => true,
            'count' => $challans->count(),
            'data' => $challans,
        ], 200);
    }

    /**
     * Get pending challans for a location
     */
    public function pendingForLocation(Request $request)
    {
        $request->validate([
            'location_type' => 'required|string',
            'location_id' => 'required|integer',
        ]);

        $challans = Challan::with([
            'items.sku:id,code,name,unit',
            'requisition:id,requisition_number',
            'driver:id,first_name,last_name',
        ])
            ->where('company_id', $request->company->id)
            ->where('to_location_type', $request->location_type)
            ->where('to_location_id', $request->location_id)
            ->whereIn('status', [Challan::STATUS_GENERATED, Challan::STATUS_IN_TRANSIT])
            ->where('is_deleted', false)
            ->orderBy('created_at', 'desc')
            ->get();

        // Add location info
        $challans->each(function ($challan) {
            $challan->from_location = $challan->getFromLocation();
            $challan->to_location = $challan->getToLocation();
        });

        return response()->json([
            'title' => 'Pending Challans',
            'sub-title' => 'Pending challans for location',
            'success' => true,
            'count' => $challans->count(),
            'data' => $challans,
        ], 200);
    }

    /**
     * Soft delete a challan
     */
    public function clear(Request $request, $id)
    {
        $challan = Challan::where('id', $id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$challan) {
            return response()->json([
                'title' => 'Challan',
                'sub-title' => 'Challan not found',
                'success' => false,
            ], 404);
        }

        if (in_array($challan->status, [Challan::STATUS_IN_TRANSIT, Challan::STATUS_DELIVERED])) {
            return response()->json([
                'title' => 'Challan',
                'sub-title' => 'Cannot delete challan that is in transit or delivered',
                'success' => false,
            ], 400);
        }

        $challan->update(['is_deleted' => true]);

        return response()->json([
            'title' => 'Challan',
            'sub-title' => 'Challan deleted successfully',
            'success' => true,
        ], 200);
    }
}
