<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashCollection extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::CashCollection;

    protected $fillable = [
        'retailer_id',
        'sales_order_id',
        'amount_due',
        'amount_collected',
        'collected_by',
        'collection_date',
        'status',
        'reschedule_count',
        'next_collection_date',
        'reschedule_reason',
        'notes',
        'latitude',
        'longitude',
        'payment_mode',
        'payment_reference',
        'is_deleted',
        'company_id',
    ];

    protected $casts = [
        'collection_date' => 'date',
        'next_collection_date' => 'date',
        'amount_due' => 'decimal:2',
        'amount_collected' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'reschedule_count' => 'integer',
        'is_deleted' => 'boolean',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PARTIAL = 'partial';
    const STATUS_COLLECTED = 'collected';
    const STATUS_RESCHEDULED = 'rescheduled';
    const STATUS_ESCALATED = 'escalated';

    // Max reschedules before escalation
    const MAX_RESCHEDULES = 3;

    /**
     * Check if collection should be escalated
     */
    public function shouldEscalate(): bool
    {
        return $this->reschedule_count >= self::MAX_RESCHEDULES;
    }

    /**
     * Reschedule collection
     */
    public function reschedule($nextDate, $reason)
    {
        $this->reschedule_count++;
        $this->reschedule_reason = $reason;
        $this->next_collection_date = $nextDate;

        if ($this->shouldEscalate()) {
            $this->status = self::STATUS_ESCALATED;
        } else {
            $this->status = self::STATUS_RESCHEDULED;
        }

        $this->save();
    }

    // Relationships
    public function retailer()
    {
        return $this->belongsTo(Retailer::class);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collected_by');
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

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_RESCHEDULED]);
    }

    public function scopeEscalated($query)
    {
        return $query->where('status', self::STATUS_ESCALATED);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('collection_date', today())
            ->orWhereDate('next_collection_date', today());
    }
}
