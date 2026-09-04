<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Notification;
use App\Models\PaymentReceived;
use App\Models\SalesOrder;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InvoicesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'cancel', 'markSent', 'downloadPdf']);
    }

    /**
     * Get all invoices
     */
    public function index(Request $request)
    {
        try {
            $query = Invoice::with([
                'items.sku:id,code,name,unit',
                'retailer:id,name,phone',
                'salesOrder:id,order_number',
                'creator:id,first_name,last_name',
            ]);
            $query = Utility::prepareSearchQuery($query, $request, new Invoice());
            $query->orderBy('id', 'desc');
            $invoices = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title' => 'Invoices',
                'sub-title' => 'Invoices fetched successfully',
                'success' => true,
                'data' => $invoices,
                'count' => is_array($invoices) ? count($invoices) : $invoices->count(),
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * Create invoice manually
     */
    public function store(Request $request)
    {
        $request->validate([
            'retailer_id' => 'required|exists:retailers,id',
            'sales_order_id' => 'nullable|exists:sales_orders,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'terms' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.sku_id' => 'required|exists:skus,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.tax_percentage' => 'nullable|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber($request->company->id),
            'sales_order_id' => $request->sales_order_id,
            'retailer_id' => $request->retailer_id,
            'invoice_date' => $request->invoice_date,
            'due_date' => $request->due_date ?? now()->addDays(30),
            'status' => Invoice::STATUS_DRAFT,
            'notes' => $request->notes,
            'terms' => $request->terms,
            'created_by' => $request->user()->id,
            'company_id' => $request->company->id,
        ]);

        $subtotal = 0;
        $totalTax = 0;
        $totalDiscount = 0;

        foreach ($request->items as $itemData) {
            $amount = $itemData['quantity'] * $itemData['rate'];
            $taxPercent = $itemData['tax_percentage'] ?? 0;
            $taxAmount = $amount * ($taxPercent / 100);
            $discountAmount = $itemData['discount_amount'] ?? 0;

            $invoice->items()->create([
                'sku_id' => $itemData['sku_id'],
                'quantity' => $itemData['quantity'],
                'rate' => $itemData['rate'],
                'tax_percentage' => $taxPercent,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'amount' => $amount,
            ]);

            $subtotal += $amount;
            $totalTax += $taxAmount;
            $totalDiscount += $discountAmount;
        }

        $total = $subtotal + $totalTax - $totalDiscount;
        $invoice->update([
            'subtotal' => $subtotal,
            'tax_amount' => $totalTax,
            'discount_amount' => $totalDiscount,
            'total' => $total,
            'balance_due' => $total,
        ]);

        return response()->json([
            'title' => 'Invoice',
            'sub-title' => 'Invoice created',
            'success' => true,
            'data' => $invoice->load(['items.sku:id,code,name,unit', 'retailer:id,name,phone']),
        ], 200);
    }

    /**
     * Create invoice from Sales Order
     */
    public function createFromSalesOrder(Request $request, $salesOrderId)
    {
        $salesOrder = SalesOrder::with('items.sku')
            ->where('id', $salesOrderId)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$salesOrder) {
            return response()->json([
                'title' => 'Invoice',
                'sub-title' => 'Sales order not found',
                'success' => false,
            ], 404);
        }

        // Check if invoice already exists for this SO
        $existingInvoice = Invoice::where('sales_order_id', $salesOrder->id)
            ->where('is_deleted', false)
            ->where('status', '!=', Invoice::STATUS_CANCELLED)
            ->first();

        if ($existingInvoice) {
            return response()->json([
                'title' => 'Invoice',
                'sub-title' => 'Invoice already exists for this sales order',
                'success' => false,
                'data' => $existingInvoice,
            ], 400);
        }

        $invoice = Invoice::createFromSalesOrder($salesOrder, $request->user()->id);

        // Notify retailer about invoice
        $retailerUser = User::where('retailer_id', $salesOrder->retailer_id)->first();
        if ($retailerUser) {
            Notification::send(
                $retailerUser->id,
                'Invoice Generated: ' . $invoice->invoice_number,
                'Invoice ' . $invoice->invoice_number . ' generated for SO ' . ($salesOrder->order_number ?? '') . '. Amount: Rs.' . number_format($invoice->total, 2),
                'invoice',
                ['invoice_id' => $invoice->id, 'sales_order_id' => $salesOrder->id, 'action' => 'created'],
                $request->company->id
            );
        }

        return response()->json([
            'title' => 'Invoice',
            'sub-title' => 'Invoice created from sales order',
            'success' => true,
            'data' => $invoice->load(['items.sku:id,code,name,unit', 'retailer:id,name,phone']),
        ], 200);
    }

    /**
     * Get a single invoice
     */
    public function show(Request $request)
    {
        $invoice = Invoice::with([
            'items.sku:id,code,name,unit',
            'retailer:id,name,phone,address',
            'salesOrder:id,order_number,status',
            'payments',
            'creator:id,first_name,last_name',
        ])
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$invoice) {
            return response()->json([
                'title' => 'Invoice',
                'sub-title' => 'Invoice not found',
                'success' => false,
            ], 404);
        }

        return response()->json([
            'title' => 'Invoice',
            'sub-title' => 'Invoice fetched successfully',
            'success' => true,
            'data' => $invoice,
        ], 200);
    }

    /**
     * Mark invoice as sent
     */
    public function markSent(Request $request)
    {
        $invoice = Invoice::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$invoice) {
            return response()->json([
                'title' => 'Invoice',
                'sub-title' => 'Invoice not found',
                'success' => false,
            ], 404);
        }

        if ($invoice->status !== Invoice::STATUS_DRAFT) {
            return response()->json([
                'title' => 'Invoice',
                'sub-title' => 'Only draft invoices can be marked as sent',
                'success' => false,
            ], 400);
        }

        $invoice->update(['status' => Invoice::STATUS_SENT]);

        return response()->json([
            'title' => 'Invoice',
            'sub-title' => 'Invoice marked as sent',
            'success' => true,
            'data' => $invoice,
        ], 200);
    }

    /**
     * Record payment against invoice
     */
    public function recordPayment(Request $request, $invoiceId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_mode' => 'required|string|in:cash,bank_transfer,upi,cheque',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $invoice = Invoice::where('id', $invoiceId)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$invoice) {
            return response()->json([
                'title' => 'Payment',
                'sub-title' => 'Invoice not found',
                'success' => false,
            ], 404);
        }

        if (in_array($invoice->status, [Invoice::STATUS_PAID, Invoice::STATUS_CANCELLED])) {
            return response()->json([
                'title' => 'Payment',
                'sub-title' => 'Cannot record payment for this invoice',
                'success' => false,
            ], 400);
        }

        if ($request->amount > $invoice->balance_due) {
            return response()->json([
                'title' => 'Payment',
                'sub-title' => 'Payment amount exceeds balance due (₹' . $invoice->balance_due . ')',
                'success' => false,
            ], 400);
        }

        $payment = PaymentReceived::recordPayment($invoice, $request->all(), $request->user()->id, $request->company->id);

        // Notify retailer about payment
        $retailerUser = User::where('retailer_id', $invoice->retailer_id)->first();
        if ($retailerUser) {
            Notification::send(
                $retailerUser->id,
                'Payment Received: Rs.' . number_format($request->amount, 2),
                'Payment of Rs.' . number_format($request->amount, 2) . ' recorded against Invoice ' . $invoice->invoice_number . '. Balance: Rs.' . number_format($invoice->fresh()->balance_due, 2),
                'payment',
                ['invoice_id' => $invoice->id, 'payment_id' => $payment->id, 'action' => 'received'],
                $request->company->id
            );
        }

        return response()->json([
            'title' => 'Payment',
            'sub-title' => 'Payment recorded successfully',
            'success' => true,
            'data' => $payment,
            'invoice' => $invoice->fresh(['payments']),
        ], 200);
    }

    /**
     * Download invoice PDF
     */
    public function downloadPdf(Request $request)
    {
        $invoice = Invoice::with([
            'items.sku',
            'retailer',
            'salesOrder:id,order_number',
            'payments',
            'creator:id,first_name,last_name',
            'company',
        ])
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$invoice) {
            return response()->json([
                'title' => 'Invoice',
                'sub-title' => 'Invoice not found',
                'success' => false,
            ], 404);
        }

        $amountInWords = $this->convertNumberToWords($invoice->total);

        ini_set('memory_limit', '1G');
        $pdf = Pdf::loadView('pdfs.invoice', [
            'invoice' => $invoice,
            'company' => $invoice->company,
            'retailer' => $invoice->retailer,
            'amountInWords' => 'Rupees ' . $amountInWords . ' Only',
        ]);

        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }

    /**
     * Convert number to words for invoice
     */
    private function convertNumberToWords($number): string
    {
        $number = round($number, 2);
        $wholeNumber = (int) $number;
        $decimal = round(($number - $wholeNumber) * 100);

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
            'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
            'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $convert = function ($num) use ($ones, $tens, &$convert) {
            if ($num < 20) return $ones[$num];
            if ($num < 100) return $tens[(int)($num / 10)] . ($num % 10 ? ' ' . $ones[$num % 10] : '');
            if ($num < 1000) return $ones[(int)($num / 100)] . ' Hundred' . ($num % 100 ? ' and ' . $convert($num % 100) : '');
            if ($num < 100000) return $convert((int)($num / 1000)) . ' Thousand' . ($num % 1000 ? ' ' . $convert($num % 1000) : '');
            if ($num < 10000000) return $convert((int)($num / 100000)) . ' Lakh' . ($num % 100000 ? ' ' . $convert($num % 100000) : '');
            return $convert((int)($num / 10000000)) . ' Crore' . ($num % 10000000 ? ' ' . $convert($num % 10000000) : '');
        };

        $result = $wholeNumber > 0 ? $convert($wholeNumber) : 'Zero';
        if ($decimal > 0) {
            $result .= ' and ' . $convert($decimal) . ' Paise';
        }

        return $result;
    }

    /**
     * Cancel invoice
     */
    public function cancel(Request $request)
    {
        $invoice = Invoice::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$invoice) {
            return response()->json([
                'title' => 'Invoice',
                'sub-title' => 'Invoice not found',
                'success' => false,
            ], 404);
        }

        if ($invoice->amount_paid > 0) {
            return response()->json([
                'title' => 'Invoice',
                'sub-title' => 'Cannot cancel invoice with payments recorded',
                'success' => false,
            ], 400);
        }

        $invoice->update(['status' => Invoice::STATUS_CANCELLED]);

        return response()->json([
            'title' => 'Invoice',
            'sub-title' => 'Invoice cancelled',
            'success' => true,
            'data' => $invoice,
        ], 200);
    }
}
