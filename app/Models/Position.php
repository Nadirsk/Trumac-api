<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::Position;

    protected $fillable = [
        'is_active',
        'deleted_at',
        'company_id',
        'role_id',
        'name'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'position_permissions',  'position_id', 'permission_id')->with('module');
    }
    public function position_permissions(): HasMany
    {
        return $this->hasMany(PositionPermission::class)->with('permission');
    }
}
