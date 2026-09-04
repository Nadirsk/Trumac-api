<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'sku_id',
        'quantity',
        'received_quantity',
        'unit_price',
        'tax_percent',
        'tax_amount',
        'discount_percent',
        'discount_amount',
        'total',
        'company_id',
    ];

    protected $casts = [
        'quantity'          => 'decimal:2',
        'received_quantity' => 'decimal:2',
        'unit_price'        => 'decimal:2',
        'tax_percent'       => 'decimal:2',
        'tax_amount'        => 'decimal:2',
        'discount_percent'  => 'decimal:2',
        'discount_amount'   => 'decimal:2',
        'total'             => 'decimal:2',
    ];

    /**
     * Calculate item totals
     */
    public function calculateTotal(): void
    {
        $subtotal = $this->quantity * $this->unit_price;
        $taxAmount = $subtotal * ($this->tax_percent / 100);
        $discountAmount = $subtotal * ($this->discount_percent / 100);
        $total = $subtotal + $taxAmount - $discountAmount;

        $this->update([
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total' => $total,
        ]);
    }

    // Relationships
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }
}
