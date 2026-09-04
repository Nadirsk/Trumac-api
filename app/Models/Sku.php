<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sku extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::Sku;

    protected $fillable = [
        'is_active',
        'is_deleted',
        'company_id',
        'category_id',
        'code',
        'name',
        'description',
        'unit',
        'mrp',
        'selling_price',
        'purchase_price',
        'hsn_code',
        'gst_percent',
        'out_of_stock_threshold',
        'barcode',
    ];

    protected $casts = [
        'mrp' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'gst_percent' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SkuCategory::class, 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(SkuImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasMany
    {
        return $this->hasMany(SkuImage::class)->where('is_primary', true);
    }
}
