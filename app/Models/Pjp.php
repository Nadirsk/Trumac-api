<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pjp extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::Pjp;

    protected $fillable = [
        'is_active',
        'is_deleted',
        'company_id',
        'employee_id',
        'franchise_id',
        'day_of_week',
        'name',
        'notes',
    ];

    /**
     * Day of week constants
     */
    const MONDAY = 1;
    const TUESDAY = 2;
    const WEDNESDAY = 3;
    const THURSDAY = 4;
    const FRIDAY = 5;
    const SATURDAY = 6;
    const SUNDAY = 7;

    /**
     * Get day name from day_of_week value
     */
    public static function getDayName(int $dayOfWeek): string
    {
        $days = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];

        return $days[$dayOfWeek] ?? 'Unknown';
    }

    /**
     * Get all days as array
     */
    public static function getDaysArray(): array
    {
        return [
            ['value' => 1, 'label' => 'Monday'],
            ['value' => 2, 'label' => 'Tuesday'],
            ['value' => 3, 'label' => 'Wednesday'],
            ['value' => 4, 'label' => 'Thursday'],
            ['value' => 5, 'label' => 'Friday'],
            ['value' => 6, 'label' => 'Saturday'],
            ['value' => 7, 'label' => 'Sunday'],
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function franchise(): BelongsTo
    {
        return $this->belongsTo(Franchise::class);
    }

    public function pjpRetailers(): HasMany
    {
        return $this->hasMany(PjpRetailer::class)->orderBy('sequence');
    }

    public function retailers(): BelongsToMany
    {
        return $this->belongsToMany(Retailer::class, 'pjp_retailers')
            ->withPivot(['sequence', 'planned_visit_time', 'expected_duration', 'is_active'])
            ->withTimestamps()
            ->orderBy('pjp_retailers.sequence');
    }

    public function changes(): HasMany
    {
        return $this->hasMany(PjpChange::class);
    }

    public function pendingChanges(): HasMany
    {
        return $this->hasMany(PjpChange::class)->where('status', 'pending');
    }

    /**
     * Get the day name attribute
     */
    public function getDayNameAttribute(): string
    {
        return self::getDayName($this->day_of_week);
    }

    /**
     * Get retailer count
     */
    public function getRetailerCountAttribute(): int
    {
        return $this->pjpRetailers()->where('is_active', true)->count();
    }
}
