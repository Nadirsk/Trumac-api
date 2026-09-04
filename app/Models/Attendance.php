<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enum\SearchModelParams;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::Attendance;

    protected $fillable = [
        'user_id',
        'date',
        'punch_in_time',
        'punch_out_time',
        'punch_in_latitude',
        'punch_in_longitude',
        'punch_out_latitude',
        'punch_out_longitude',
        'punch_in_address',
        'punch_out_address',
        'status',
        'total_hours',
        'notes',
        'is_deleted',
        'company_id',
    ];

    protected $casts = [
        'date' => 'date',
        'punch_in_time' => 'datetime',
        'punch_out_time' => 'datetime',
        'punch_in_latitude' => 'decimal:8',
        'punch_in_longitude' => 'decimal:8',
        'punch_out_latitude' => 'decimal:8',
        'punch_out_longitude' => 'decimal:8',
        'total_hours' => 'decimal:2',
        'is_deleted' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Accessors
    public function getIsPunchedInAttribute()
    {
        return $this->punch_in_time !== null && $this->punch_out_time === null;
    }

    public function getIsPunchedOutAttribute()
    {
        return $this->punch_in_time !== null && $this->punch_out_time !== null;
    }

    public function getFormattedTotalHoursAttribute()
    {
        if (!$this->total_hours) return '-';
        $hours = floor($this->total_hours);
        $minutes = round(($this->total_hours - $hours) * 60);
        return "{$hours}h {$minutes}m";
    }

    // Calculate total hours when punching out
    public function calculateTotalHours()
    {
        if ($this->punch_in_time && $this->punch_out_time) {
            $punchIn = Carbon::parse($this->punch_in_time);
            $punchOut = Carbon::parse($this->punch_out_time);
            $this->total_hours = $punchOut->diffInMinutes($punchIn) / 60;
            $this->save();
        }
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('date', Carbon::today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('date', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek(),
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
