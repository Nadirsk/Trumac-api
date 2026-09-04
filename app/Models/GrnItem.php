<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'grn_id',
        'sku_id',
        'expected_quantity',
        'received_quantity',
        'rejected_quantity',
        'batch_number',
        'expiry_date',
        'manufacturing_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'expected_quantity' => 'decimal:2',
        'received_quantity' => 'decimal:2',
        'rejected_quantity' => 'decimal:2',
        'expiry_date' => 'date',
        'manufacturing_date' => 'date',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_RECEIVED = 'received';
    const STATUS_PARTIAL = 'partial';
    const STATUS_REJECTED = 'rejected';

    /**
     * Get variance (expected - received)
     */
    public function getVarianceAttribute(): float
    {
        return $this->expected_quantity - $this->received_quantity;
    }

    /**
     * Check if fully received
     */
    public function isFullyReceived(): bool
    {
        return $this->received_quantity >= $this->expected_quantity;
    }

    // Relationships
    public function grn()
    {
        return $this->belongsTo(Grn::class);
    }

    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }
}
