<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Value extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::Value;

    protected $fillable = [
        'is_active',
        'is_deleted',
        'company_id',
        'name',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function value_lists(): HasMany
    {
        return $this->hasMany(ValueList::class)
            ->where('is_deleted', '=', FALSE)
            ->with('value');
    }

    public function active_value_lists(): HasMany
    {
        return $this->hasMany(ValueList::class)
            ->where('is_deleted', '=', FALSE)
            ->where('is_active', '=', 1);
    }
}
