<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChallanItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'challan_id',
        'sku_id',
        'requisition_item_id',
        'sales_order_item_id',
        'quantity',
        'received_quantity',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'received_quantity' => 'decimal:2',
    ];

    /**
     * Get pending quantity to receive
     */
    public function getPendingQuantityAttribute(): float
    {
        return max(0, $this->quantity - $this->received_quantity);
    }

    // Relationships
    public function challan()
    {
        return $this->belongsTo(Challan::class);
    }

    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }

    public function requisitionItem()
    {
        return $this->belongsTo(RequisitionItem::class);
    }
}
