<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JourneyStop extends Model
{
    use HasFactory;

    protected $fillable = [
        'journey_plan_id',
        'stop_type',
        'reference_type',
        'reference_id',
        'sequence',
        'name',
        'address',
        'latitude',
        'longitude',
        'planned_time',
        'arrival_time',
        'departure_time',
        'arrival_latitude',
        'arrival_longitude',
        'departure_latitude',
        'departure_longitude',
        'signature_path',
        'status',
        'notes',
        'skip_reason',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'arrival_latitude' => 'decimal:7',
        'arrival_longitude' => 'decimal:7',
        'departure_latitude' => 'decimal:7',
        'departure_longitude' => 'decimal:7',
        'arrival_time' => 'datetime',
        'departure_time' => 'datetime',
    ];

    // Stop types
    const TYPE_DELIVERY = 'delivery';
    const TYPE_PICKUP = 'pickup';
    const TYPE_RETAILER = 'retailer';
    const TYPE_WAREHOUSE = 'warehouse';
    const TYPE_GODOWN = 'godown';
    const TYPE_FRANCHISE = 'franchise';

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ARRIVED = 'arrived';
    const STATUS_COMPLETED = 'completed';
    const STATUS_SKIPPED = 'skipped';

    /**
     * Check if stop can be arrived at
     */
    public function canArrive(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if stop can be completed
     */
    public function canComplete(): bool
    {
        return $this->status === self::STATUS_ARRIVED;
    }

    /**
     * Mark as arrived
     */
    public function arrive($latitude = null, $longitude = null)
    {
        $this->update([
            'status' => self::STATUS_ARRIVED,
            'arrival_time' => now(),
            'arrival_latitude' => $latitude,
            'arrival_longitude' => $longitude,
        ]);
    }

    /**
     * Mark as completed with signature
     */
    public function complete($signaturePath = null, $latitude = null, $longitude = null, $notes = null)
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'departure_time' => now(),
            'departure_latitude' => $latitude,
            'departure_longitude' => $longitude,
            'signature_path' => $signaturePath,
            'notes' => $notes,
        ]);
    }

    /**
     * Skip the stop with reason
     */
    public function skip($reason)
    {
        $this->update([
            'status' => self::STATUS_SKIPPED,
            'skip_reason' => $reason,
        ]);
    }

    /**
     * Get time spent at stop in minutes
     */
    public function getTimeSpentAttribute(): ?int
    {
        if ($this->arrival_time && $this->departure_time) {
            return $this->arrival_time->diffInMinutes($this->departure_time);
        }
        return null;
    }

    // Relationships
    public function journeyPlan()
    {
        return $this->belongsTo(JourneyPlan::class);
    }

    /**
     * Get the reference model (polymorphic)
     */
    public function getReference()
    {
        if (!$this->reference_type || !$this->reference_id) {
            return null;
        }

        $modelMap = [
            'sales_order' => SalesOrder::class,
            'retailer' => Retailer::class,
            'warehouse' => Warehouse::class,
            'company_godown' => CompanyGodown::class,
            'franchise' => Franchise::class,
        ];

        $modelClass = $modelMap[$this->reference_type] ?? null;
        if ($modelClass) {
            return $modelClass::find($this->reference_id);
        }

        return null;
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
}
