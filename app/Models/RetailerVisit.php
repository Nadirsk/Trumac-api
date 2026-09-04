<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetailerVisit extends Model
{
    use HasFactory;

    // Searchable fields for Utility::prepareSearchQuery
    public static $searchable = SearchModelParams::RetailerVisit;

    // Visit type constants
    const TYPE_PLANNED = 'planned';
    const TYPE_UNPLANNED = 'unplanned';

    protected $fillable = [
        'user_id',
        'retailer_id',
        'pjp_id',
        'company_id',
        'visit_type',
        'visit_date',
        'check_in_time',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_accuracy',
        'check_in_distance',
        'check_out_time',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_accuracy',
        'check_out_distance',
        'notes',
        'device_id',
        'is_active',
        'is_deleted',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'check_in_accuracy' => 'decimal:2',
        'check_in_distance' => 'decimal:2',
        'check_out_accuracy' => 'decimal:2',
        'check_out_distance' => 'decimal:2',
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function retailer(): BelongsTo
    {
        return $this->belongsTo(Retailer::class);
    }

    public function pjp(): BelongsTo
    {
        return $this->belongsTo(Pjp::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
