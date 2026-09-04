<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Notification;
use App\Models\PjpChange;
use App\Models\Pjp;
use App\Models\PjpRetailer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class PjpChangesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'approve', 'reject']);
    }

    /**
     * Get all PJP change requests
     */
    public function index(Request $request)
    {
        try {
            $query = PjpChange::query();
            $query = Utility::prepareSearchQuery($query, $request, new PjpChange());
            $query->orderBy('id', 'desc');
            $pjpChanges = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title' => 'PJP Changes',
                'sub-title' => 'PJP change requests fetched successfully',
                'success' => true,
                'data' => $pjpChanges,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * Create a new PJP change request
     */
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'pjp_id' => 'required|exists:pjps,id',
            'date' => 'required|date|after:today',
            'action' => 'required|in:add,remove,swap,reorder',
            'retailer_id' => 'required_if:action,add,remove,swap|exists:retailers,id',
            'swap_retailer_id' => 'required_if:action,swap|exists:retailers,id',
            'reason' => 'required|string|max:500',
        ]);

        // Validate the date is at least 1 day in advance
        if (!PjpChange::isValidChangeDate($attributes['date'])) {
            return response()->json([
                'title' => 'PJP Change',
                'sub-title' => 'Change request must be submitted at least 1 day in advance',
                'success' => false,
            ], 400);
        }

        // Verify PJP belongs to the user or user has permission
        $pjp = Pjp::where('id', $attributes['pjp_id'])
            ->where('company_id', $request->company->id)
            ->first();

        if (!$pjp) {
            return response()->json([
                'title' => 'PJP Change',
                'sub-title' => 'PJP not found',
                'success' => false,
            ], 404);
        }

        // Check if there's already a pending change for same PJP, date, and retailer
        $existingChange = PjpChange::where('pjp_id', $attributes['pjp_id'])
            ->where('date', $attributes['date'])
            ->where('retailer_id', $attributes['retailer_id'] ?? null)
            ->where('status', 'pending')
            ->where('is_deleted', false)
            ->first();

        if ($existingChange) {
            return response()->json([
                'title' => 'PJP Change',
                'sub-title' => 'A pending change request already exists for this date and retailer',
                'success' => false,
            ], 400);
        }

        $attributes['company_id'] = $request->company->id;
        $attributes['requested_by'] = $request->user()->id;
        $attributes['status'] = 'pending';

        $change = PjpChange::create($attributes);

        return response()->json([
            'title' => 'PJP Change',
            'sub-title' => 'Change request submitted successfully',
            'success' => true,
            'data' => $change->load(['pjp', 'retailer', 'requestedBy:id,first_name,last_name']),
        ], 200);
    }

    /**
     * Get a single change request
     */
    public function show(Request $request)
    {
        $change = PjpChange::with([
            'pjp:id,name,day_of_week,employee_id',
            'pjp.employee:id,first_name,last_name',
            'retailer:id,name,shop_name,phone',
            'swapRetailer:id,name,shop_name,phone',
            'requestedBy:id,first_name,last_name',
            'approvedBy:id,first_name,last_name',
        ])
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$change) {
            return response()->json([
                'title' => 'PJP Change',
                'sub-title' => 'Change request not found',
                'success' => false,
            ], 404);
        }

        return response()->json([
            'title' => 'PJP Change',
            'sub-title' => 'Change request fetched successfully',
            'success' => true,
            'data' => $change,
        ], 200);
    }

    /**
     * Approve a change request
     */
    public function approve(Request $request)
    {
        $change = PjpChange::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$change) {
            return response()->json([
                'title' => 'PJP Change',
                'sub-title' => 'Change request not found',
                'success' => false,
            ], 404);
        }

        if ($change->status !== 'pending') {
            return response()->json([
                'title' => 'PJP Change',
                'sub-title' => 'Change request is already ' . $change->status,
                'success' => false,
            ], 400);
        }

        // Apply the actual change to PJP based on action type
        $this->applyPjpChange($change);

        $change->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'remarks' => $request->input('remarks'),
            'approved_at' => Carbon::now(),
        ]);

        // Notify the employee whose PJP was changed
        $pjp = Pjp::find($change->pjp_id);
        if ($pjp && $pjp->employee_id) {
            $dayName = Pjp::getDayName($pjp->day_of_week);
            Notification::send(
                $pjp->employee_id,
                'PJP Change Approved: ' . $dayName,
                'Your ' . $dayName . ' journey plan has been changed (' . $change->action . '). Check your updated PJP.',
                'pjp',
                ['pjp_id' => $pjp->id, 'pjp_change_id' => $change->id, 'action' => 'change_approved'],
                $request->company->id
            );
        }

        return response()->json([
            'title' => 'PJP Change',
            'sub-title' => 'Change request approved and applied successfully',
            'success' => true,
            'data' => $change->fresh(['pjp', 'retailer', 'approvedBy:id,first_name,last_name']),
        ], 200);
    }

    /**
     * Apply the actual PJP change (add/remove/swap retailer)
     */
    private function applyPjpChange(PjpChange $change)
    {
        switch ($change->action) {
            case 'remove':
                // Remove retailer from PJP
                PjpRetailer::where('pjp_id', $change->pjp_id)
                    ->where('retailer_id', $change->retailer_id)
                    ->delete();
                break;

            case 'add':
                // Check if retailer already exists in PJP
                $exists = PjpRetailer::where('pjp_id', $change->pjp_id)
                    ->where('retailer_id', $change->retailer_id)
                    ->exists();

                if (!$exists) {
                    // Get next sequence number
                    $maxSequence = PjpRetailer::where('pjp_id', $change->pjp_id)->max('sequence') ?? 0;

                    // Add retailer to PJP with effective_from date
                    // If change date is today or past, set NULL (immediately active)
                    // If change date is future, set effective_from so it only shows from that date
                    $effectiveFrom = $change->date->isFuture() ? $change->date->toDateString() : null;

                    PjpRetailer::create([
                        'pjp_id' => $change->pjp_id,
                        'retailer_id' => $change->retailer_id,
                        'sequence' => $maxSequence + 1,
                        'is_active' => true,
                        'effective_from' => $effectiveFrom,
                    ]);
                }
                break;

            case 'swap':
                // Get both retailers' sequences
                $retailer1 = PjpRetailer::where('pjp_id', $change->pjp_id)
                    ->where('retailer_id', $change->retailer_id)
                    ->first();

                $retailer2 = PjpRetailer::where('pjp_id', $change->pjp_id)
                    ->where('retailer_id', $change->swap_retailer_id)
                    ->first();

                if ($retailer1 && $retailer2) {
                    // Swap their sequences
                    $tempSequence = $retailer1->sequence;
                    $retailer1->update(['sequence' => $retailer2->sequence]);
                    $retailer2->update(['sequence' => $tempSequence]);
                }
                break;
        }
    }

    /**
     * Reject a change request
     */
    public function reject(Request $request)
    {
        $request->validate([
            'remarks' => 'required|string|max:500',
        ]);

        $change = PjpChange::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$change) {
            return response()->json([
                'title' => 'PJP Change',
                'sub-title' => 'Change request not found',
                'success' => false,
            ], 404);
        }

        if ($change->status !== 'pending') {
            return response()->json([
                'title' => 'PJP Change',
                'sub-title' => 'Change request is already ' . $change->status,
                'success' => false,
            ], 400);
        }

        $change->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'remarks' => $request->remarks,
            'approved_at' => Carbon::now(),
        ]);

        return response()->json([
            'title' => 'PJP Change',
            'sub-title' => 'Change request rejected',
            'success' => true,
            'data' => $change->fresh(['pjp', 'retailer', 'approvedBy:id,first_name,last_name']),
        ], 200);
    }

    /**
     * Get my change requests
     */
    public function myRequests(Request $request)
    {
        $query = PjpChange::with([
            'pjp:id,name,day_of_week',
            'retailer:id,name,shop_name',
            'approvedBy:id,first_name,last_name',
        ])
            ->where('requested_by', $request->user()->id)
            ->where('company_id', $request->company->id)
            ->where('is_deleted', false)
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $items = $query->limit(50)->get();

        return response()->json([
            'title' => 'My PJP Change Requests',
            'sub-title' => 'Change requests fetched successfully',
            'success' => true,
            'count' => $items->count(),
            'data' => $items,
        ], 200);
    }

    /**
     * Get pending approvals
     */
    public function pendingApprovals(Request $request)
    {
        $query = PjpChange::with([
            'pjp:id,name,day_of_week,employee_id',
            'pjp.employee:id,first_name,last_name',
            'retailer:id,name,shop_name',
            'requestedBy:id,first_name,last_name',
        ])
            ->where('company_id', $request->company->id)
            ->where('status', 'pending')
            ->where('is_deleted', false)
            ->orderBy('date', 'desc');

        $items = $query->get();

        return response()->json([
            'title' => 'Pending PJP Changes',
            'sub-title' => 'Pending change requests fetched',
            'success' => true,
            'count' => $items->count(),
            'data' => $items,
        ], 200);
    }

    /**
     * Cancel own change request
     */
    public function cancel(Request $request, $id)
    {
        $change = PjpChange::where('id', $id)
            ->where('requested_by', $request->user()->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$change) {
            return response()->json([
                'title' => 'PJP Change',
                'sub-title' => 'Change request not found',
                'success' => false,
            ], 404);
        }

        if ($change->status !== 'pending') {
            return response()->json([
                'title' => 'PJP Change',
                'sub-title' => 'Only pending requests can be cancelled',
                'success' => false,
            ], 400);
        }

        $change->update(['is_deleted' => true]);

        return response()->json([
            'title' => 'PJP Change',
            'sub-title' => 'Change request cancelled',
            'success' => true,
        ], 200);
    }
}
