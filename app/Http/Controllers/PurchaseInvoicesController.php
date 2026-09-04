<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\PurchaseInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PurchaseInvoicesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'downloadPdf']);
    }

    /**
     * Get all purchase invoices
     */
    public function index(Request $request)
    {
        try {
            $query = PurchaseInvoice::with([
                'items.sku:id,code,name,unit',
                'grn:id,grn_number',
                'requisition:id,requisition_number',
            ])
                ->where('is_deleted', false);

            $query = Utility::prepareSearchQuery($query, $request, new PurchaseInvoice());
            $query->orderBy('id', 'desc');
            $invoices = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title' => 'Purchase Invoices',
                'sub-title' => 'Purchase invoices fetched successfully',
                'success' => true,
                'data' => $invoices,
                'count' => is_array($invoices) ? count($invoices) : $invoices->count(),
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get a single purchase invoice
     */
    public function show(Request $request)
    {
        $invoice = PurchaseInvoice::with([
            'items.sku',
            'grn:id,grn_number,received_at',
            'requisition:id,requisition_number,from_location_type,from_location_id,to_location_type,to_location_id',
            'company',
        ])
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$invoice) {
            return response()->json([
                'title' => 'Purchase Invoice',
                'sub-title' => 'Purchase invoice not found',
                'success' => false,
            ], 404);
        }

        return response()->json([
            'title' => 'Purchase Invoice',
            'sub-title' => 'Purchase invoice fetched successfully',
            'success' => true,
            'data' => $invoice,
        ], 200);
    }

    /**
     * Download purchase invoice PDF
     */
    public function downloadPdf(Request $request)
    {
        $invoice = PurchaseInvoice::with([
            'items.sku',
            'grn',
            'requisition',
            'company',
        ])
            ->where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$invoice) {
            return response()->json([
                'title' => 'Purchase Invoice',
                'sub-title' => 'Purchase invoice not found',
                'success' => false,
            ], 404);
        }

        $fromLocation = $invoice->getFromLocation();
        $toLocation = $invoice->getToLocation();

        $amountInWords = $this->convertNumberToWords($invoice->total_amount);

        ini_set('memory_limit', '1G');
        $pdf = Pdf::loadView('pdfs.purchase-invoice', [
            'invoice' => $invoice,
            'company' => $invoice->company,
            'fromLocation' => $fromLocation,
            'toLocation' => $toLocation,
            'items' => $invoice->items,
            'amountInWords' => 'Rupees ' . $amountInWords . ' Only',
        ]);

        return $pdf->download('purchase-invoice-' . $invoice->invoice_number . '.pdf');
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
}
