<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Retailer extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::Retailer;

    protected $fillable = [
        'is_active',
        'is_deleted',
        'company_id',
        'franchise_id',
        'company_godown_id',
        'location_id',
        'name',
        'shop_name',
        'image',
        'phone',
        'email',
        'address',
        'city',
        'pincode',
        'latitude',
        'longitude',
        'registration_type',
        'gst_no',
        'pan_no',
        'rating',
        'is_flagged',
        'credit_limit',
        'outstanding_amount',
        'created_by',
        'last_order_date',
        'total_orders',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
        'is_flagged' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'rating' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
        'last_order_date' => 'datetime',
        'total_orders' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function franchise(): BelongsTo
    {
        return $this->belongsTo(Franchise::class);
    }

    public function companyGodown(): BelongsTo
    {
        return $this->belongsTo(CompanyGodown::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(RetailerRating::class);
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    /**
     * Update the average rating based on all ratings
     */
    public function updateAverageRating(): void
    {
        $avgRating = $this->ratings()->avg('rating') ?? 0;
        $this->update(['rating' => round($avgRating, 2)]);
    }
}
