<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\PaymentReceived;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentsReceivedController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show']);
    }

    /**
     * Get all payments received
     */
    public function index(Request $request)
    {
        try {
            $query = PaymentReceived::with([
                'invoice:id,invoice_number,total,balance_due,status',
                'retailer:id,name,phone',
                'creator:id,first_name,last_name',
            ]);
            $query = Utility::prepareSearchQuery($query, $request, new PaymentReceived());
            $query->orderBy('id', 'desc');
            $payments = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title' => 'Payments Received',
                'sub-title' => 'Payments fetched successfully',
                'success' => true,
                'data' => $payments,
                'count' => is_array($payments) ? count($payments) : $payments->count(),
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get a single payment
     */
    public function show(Request $request)
    {
        $payment = PaymentReceived::with([
            'invoice:id,invoice_number,total,amount_paid,balance_due,status,retailer_id',
            'invoice.retailer:id,name,phone,address',
            'retailer:id,name,phone',
            'creator:id,first_name,last_name',
        ])
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$payment) {
            return response()->json([
                'title' => 'Payment',
                'sub-title' => 'Payment not found',
                'success' => false,
            ], 404);
        }

        return response()->json([
            'title' => 'Payment',
            'sub-title' => 'Payment fetched successfully',
            'success' => true,
            'data' => $payment,
        ], 200);
    }
}
