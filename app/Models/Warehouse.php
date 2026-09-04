<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::Warehouse;

    protected $fillable = [
        'is_active',
        'is_deleted',
        'company_id',
        'location_id',
        'name',
        'code',
        'contact_person',
        'phone',
        'email',
        'capacity',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function companyGodowns(): HasMany
    {
        return $this->hasMany(CompanyGodown::class);
    }
}
