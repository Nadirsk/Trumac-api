<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Franchise extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::Franchise;

    protected $fillable = [
        'is_active',
        'is_deleted',
        'company_id',
        'warehouse_id',
        'location_id',
        'name',
        'code',
        'owner_name',
        'phone',
        'email',
        'gst_no',
        'pan_no',
        'address',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function retailers(): HasMany
    {
        return $this->hasMany(Retailer::class);
    }
}
