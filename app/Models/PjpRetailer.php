<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PjpRetailer extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_active',
        'pjp_id',
        'retailer_id',
        'sequence',
        'planned_visit_time',
        'expected_duration',
        'effective_from',
    ];

    protected $casts = [
        'planned_visit_time' => 'datetime:H:i',
        'effective_from' => 'date',
    ];

    public function pjp(): BelongsTo
    {
        return $this->belongsTo(Pjp::class);
    }

    public function retailer(): BelongsTo
    {
        return $this->belongsTo(Retailer::class);
    }
}
