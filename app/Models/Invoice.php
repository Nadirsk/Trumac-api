<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::Invoice;

    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_PAID = 'paid';
    const STATUS_PARTIALLY_PAID = 'partially_paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'invoice_number',
        'sales_order_id',
        'retailer_id',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'amount_paid',
        'balance_due',
        'status',
        'notes',
        'terms',
        'created_by',
        'company_id',
        'is_deleted',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
    ];

    /**
     * Generate invoice number: INV{YYYYMMDD}{0001}
     */
    public static function generateInvoiceNumber($companyId): string
    {
        $prefix = 'INV' . now()->format('Ymd');
        $lastInvoice = self::where('company_id', $companyId)
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return $prefix . $nextNumber;
    }

    /**
     * Create invoice from Sales Order
     */
    public static function createFromSalesOrder(SalesOrder $salesOrder, $createdBy = null): self
    {
        $invoice = self::create([
            'invoice_number' => self::generateInvoiceNumber($salesOrder->company_id),
            'sales_order_id' => $salesOrder->id,
            'retailer_id' => $salesOrder->retailer_id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => $salesOrder->subtotal,
            'tax_amount' => $salesOrder->tax_amount,
            'discount_amount' => $salesOrder->discount_amount,
            'total' => $salesOrder->total_amount,
            'amount_paid' => 0,
            'balance_due' => $salesOrder->total_amount,
            'status' => self::STATUS_DRAFT,
            'created_by' => $createdBy ?? $salesOrder->created_by,
            'company_id' => $salesOrder->company_id,
        ]);

        // Copy items from sales order
        foreach ($salesOrder->items as $soItem) {
            $invoice->items()->create([
                'sku_id' => $soItem->sku_id,
                'description' => $soItem->sku->name ?? null,
                'quantity' => $soItem->quantity,
                'rate' => $soItem->unit_price,
                'tax_percentage' => $soItem->tax_percent,
                'tax_amount' => $soItem->tax_amount,
                'discount_amount' => $soItem->discount_amount ?? 0,
                'amount' => $soItem->total,
            ]);
        }

        return $invoice;
    }

    /**
     * Recalculate totals from items
     */
    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum('amount');
        $this->tax_amount = $this->items->sum('tax_amount');
        $this->discount_amount = $this->items->sum('discount_amount');
        $this->total = $this->subtotal + $this->tax_amount - $this->discount_amount;
        $this->balance_due = $this->total - $this->amount_paid;
        $this->save();
    }

    /**
     * Update payment status based on amount_paid
     */
    public function updatePaymentStatus(): void
    {
        if ($this->amount_paid >= $this->total) {
            $this->status = self::STATUS_PAID;
            $this->balance_due = 0;
        } elseif ($this->amount_paid > 0) {
            $this->status = self::STATUS_PARTIALLY_PAID;
            $this->balance_due = $this->total - $this->amount_paid;
        } else {
            $this->balance_due = $this->total;
        }
        $this->save();
    }

    // Relationships

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function retailer(): BelongsTo
    {
        return $this->belongsTo(Retailer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentReceived::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_deleted', false);
    }
}
