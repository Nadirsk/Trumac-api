<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enum\SearchModelParams;

class LeaveType extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::LeaveType;

    protected $fillable = [
        'name',
        'is_paid',
        'max_days',
        'is_active',
        'is_deleted',
        'company_id',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
