<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkuCategory extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::SkuCategory;

    protected $fillable = [
        'is_active',
        'is_deleted',
        'company_id',
        'parent_id',
        'name',
        'description',
        'image_path',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(SkuCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(SkuCategory::class, 'parent_id');
    }

    public function skus(): HasMany
    {
        return $this->hasMany(Sku::class, 'category_id');
    }
}
