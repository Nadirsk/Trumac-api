<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enum\SearchModelParams;
use Carbon\Carbon;

class LeaveRequest extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::LeaveRequest;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'from_date',
        'to_date',
        'reason',
        'status',
        'approved_by',
        'remarks',
        'is_deleted',
        'company_id',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'is_deleted' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Accessors
    public function getTotalDaysAttribute()
    {
        $from = Carbon::parse($this->from_date);
        $to = Carbon::parse($this->to_date);
        return $from->diffInDays($to) + 1;
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('from_date', '>=', Carbon::today());
    }
}
