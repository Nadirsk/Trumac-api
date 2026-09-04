<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseInvoice extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::PurchaseInvoice;

    protected $fillable = [
        'invoice_number',
        'grn_id',
        'requisition_id',
        'sales_order_id',
        'retailer_id',
        'from_location_type',
        'from_location_id',
        'to_location_type',
        'to_location_id',
        'invoice_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'status',
        'pdf_path',
        'notes',
        'is_deleted',
        'company_id',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'is_deleted' => 'boolean',
    ];

    // Status constants
    const STATUS_GENERATED = 'generated';
    const STATUS_SENT = 'sent';
    const STATUS_PAID = 'paid';

    /**
     * Generate invoice number
     */
    public static function generateInvoiceNumber($companyId): string
    {
        $prefix = 'PI';
        $date = now()->format('Ymd');
        $lastInvoice = self::where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInvoice ? (intval(substr($lastInvoice->invoice_number, -4)) + 1) : 1;

        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create invoice from Sales Order (when retailer confirms full receipt)
     */
    public static function createFromSalesOrder(SalesOrder $salesOrder): ?self
    {
        // Determine source location from order's source_type
        $fromLocationType = $salesOrder->source_type === 'cg' ? 'company_godown' : ($salesOrder->source_type ?? 'franchise');

        $invoice = self::create([
            'invoice_number' => self::generateInvoiceNumber($salesOrder->company_id),
            'sales_order_id' => $salesOrder->id,
            'retailer_id' => $salesOrder->retailer_id,
            'from_location_type' => $fromLocationType,
            'from_location_id' => $salesOrder->source_id,
            'to_location_type' => 'retailer',
            'to_location_id' => $salesOrder->retailer_id,
            'invoice_date' => now()->toDateString(),
            'subtotal' => $salesOrder->subtotal,
            'tax_amount' => $salesOrder->tax_amount,
            'total_amount' => $salesOrder->total_amount,
            'status' => self::STATUS_GENERATED,
            'notes' => 'Generated from Sales Order #' . $salesOrder->order_number,
            'company_id' => $salesOrder->company_id,
        ]);

        // Copy items from sales order
        foreach ($salesOrder->items as $soItem) {
            $invoice->items()->create([
                'sku_id' => $soItem->sku_id,
                'quantity' => $soItem->received_quantity ?? $soItem->quantity,
                'unit_price' => $soItem->unit_price,
                'tax_percent' => $soItem->tax_percent,
                'tax_amount' => $soItem->tax_amount,
                'total' => $soItem->total,
            ]);
        }

        return $invoice;
    }

    /**
     * Create invoice from GRN (for requisitions)
     */
    public static function createFromGrn(Grn $grn): ?self
    {
        // Only create invoice for requisition-based GRNs
        if ($grn->reference_type !== Grn::REF_REQUISITION) {
            return null;
        }

        $requisition = Requisition::find($grn->reference_id);
        if (!$requisition) {
            return null;
        }

        // Create invoice
        $invoice = self::create([
            'invoice_number' => self::generateInvoiceNumber($grn->company_id),
            'grn_id' => $grn->id,
            'requisition_id' => $requisition->id,
            'from_location_type' => $requisition->from_location_type,
            'from_location_id' => $requisition->from_location_id,
            'to_location_type' => $requisition->to_location_type,
            'to_location_id' => $requisition->to_location_id,
            'invoice_date' => now()->toDateString(),
            'status' => self::STATUS_GENERATED,
            'company_id' => $grn->company_id,
        ]);

        // Add items from GRN
        $subtotal = 0;
        $taxTotal = 0;

        foreach ($grn->items as $grnItem) {
            if ($grnItem->received_quantity > 0) {
                $sku = Sku::find($grnItem->sku_id);
                $unitPrice = $sku->selling_price ?? 0;
                $taxPercent = $sku->gst_percent ?? 0;
                $lineTotal = $grnItem->received_quantity * $unitPrice;
                $lineTax = $lineTotal * ($taxPercent / 100);

                $invoice->items()->create([
                    'sku_id' => $grnItem->sku_id,
                    'quantity' => $grnItem->received_quantity,
                    'unit_price' => $unitPrice,
                    'tax_percent' => $taxPercent,
                    'tax_amount' => $lineTax,
                    'total' => $lineTotal + $lineTax,
                    'batch_number' => $grnItem->batch_number,
                    'expiry_date' => $grnItem->expiry_date,
                ]);

                $subtotal += $lineTotal;
                $taxTotal += $lineTax;
            }
        }

        // Update totals
        $invoice->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxTotal,
            'total_amount' => $subtotal + $taxTotal,
        ]);

        // Generate PDF
        $invoice->generatePdf();

        return $invoice;
    }

    /**
     * Generate PDF invoice
     */
    public function generatePdf(): ?string
    {
        try {
            $this->load(['items.sku', 'grn', 'requisition', 'company']);

            // Get location details
            $fromLocation = $this->getFromLocation();
            $toLocation = $this->getToLocation();

            $data = [
                'invoice' => $this,
                'company' => $this->company,
                'fromLocation' => $fromLocation,
                'toLocation' => $toLocation,
                'items' => $this->items,
                'amountInWords' => 'Rupees ' . number_format($this->total_amount, 2) . ' Only',
            ];

            $pdf = Pdf::loadView('pdfs.purchase-invoice', $data);

            // Store PDF
            $filename = 'invoices/' . $this->invoice_number . '.pdf';
            $path = storage_path('app/public/' . $filename);

            // Ensure directory exists
            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            $pdf->save($path);

            $this->update(['pdf_path' => 'storage/' . $filename]);

            return $this->pdf_path;
        } catch (\Exception $e) {
            \Log::error('PDF generation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get from location model
     */
    public function getFromLocation()
    {
        $modelMap = [
            'warehouse' => Warehouse::class,
            'head_office' => null,
        ];

        if ($this->from_location_type === 'head_office') {
            return (object)['name' => 'Head Office', 'id' => 0];
        }

        $modelClass = $modelMap[$this->from_location_type] ?? null;
        if ($modelClass) {
            return $modelClass::find($this->from_location_id);
        }

        return null;
    }

    /**
     * Get to location model
     */
    public function getToLocation()
    {
        $modelMap = [
            'warehouse' => Warehouse::class,
            'company_godown' => CompanyGodown::class,
            'franchise' => Franchise::class,
        ];

        $modelClass = $modelMap[$this->to_location_type] ?? null;
        if ($modelClass) {
            return $modelClass::find($this->to_location_id);
        }

        return null;
    }

    // Relationships
    public function items()
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function grn()
    {
        return $this->belongsTo(Grn::class);
    }

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_deleted', false);
    }
}
