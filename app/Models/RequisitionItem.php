<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequisitionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'requisition_id',
        'sku_id',
        'requested_quantity',
        'approved_quantity',
        'fulfilled_quantity',
        'notes',
    ];

    protected $casts = [
        'requested_quantity' => 'decimal:2',
        'approved_quantity' => 'decimal:2',
        'fulfilled_quantity' => 'decimal:2',
    ];

    /**
     * Get pending quantity (approved - fulfilled)
     */
    public function getPendingQuantityAttribute(): float
    {
        return $this->approved_quantity - $this->fulfilled_quantity;
    }

    /**
     * Check if fully fulfilled
     */
    public function isFullyFulfilled(): bool
    {
        return $this->fulfilled_quantity >= $this->approved_quantity;
    }

    // Relationships
    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }

    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }
}
