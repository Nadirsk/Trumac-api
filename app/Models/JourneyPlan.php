<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JourneyPlan extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::JourneyPlan;

    protected $fillable = [
        'driver_id',
        'date',
        'status',
        'start_time',
        'end_time',
        'start_odometer',
        'end_odometer',
        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'notes',
        'vehicle_id',
        'is_deleted',
        'company_id',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'start_odometer' => 'decimal:2',
        'end_odometer' => 'decimal:2',
        'start_latitude' => 'decimal:7',
        'start_longitude' => 'decimal:7',
        'end_latitude' => 'decimal:7',
        'end_longitude' => 'decimal:7',
        'is_deleted' => 'boolean',
    ];

    // Status constants
    const STATUS_PLANNED = 'planned';
    const STATUS_STARTED = 'started';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get distance traveled in km
     */
    public function getDistanceTraveledAttribute(): ?float
    {
        if ($this->start_odometer && $this->end_odometer) {
            return $this->end_odometer - $this->start_odometer;
        }
        return null;
    }

    /**
     * Get journey duration in minutes
     */
    public function getDurationAttribute(): ?int
    {
        if ($this->start_time && $this->end_time) {
            return $this->start_time->diffInMinutes($this->end_time);
        }
        return null;
    }

    /**
     * Check if journey can be started
     */
    public function canStart(): bool
    {
        return $this->status === self::STATUS_PLANNED;
    }

    /**
     * Check if journey can be completed
     */
    public function canComplete(): bool
    {
        return $this->status === self::STATUS_STARTED;
    }

    /**
     * Start the journey
     */
    public function start($odometer, $latitude = null, $longitude = null)
    {
        $this->update([
            'status' => self::STATUS_STARTED,
            'start_time' => now(),
            'start_odometer' => $odometer,
            'start_latitude' => $latitude,
            'start_longitude' => $longitude,
        ]);
    }

    /**
     * Complete the journey
     */
    public function complete($odometer, $latitude = null, $longitude = null)
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'end_time' => now(),
            'end_odometer' => $odometer,
            'end_latitude' => $latitude,
            'end_longitude' => $longitude,
        ]);
    }

    // Relationships
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function stops()
    {
        return $this->hasMany(JourneyStop::class)->orderBy('sequence');
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

    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    public function scopeForDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    public function scopeStarted($query)
    {
        return $query->where('status', self::STATUS_STARTED);
    }
}
