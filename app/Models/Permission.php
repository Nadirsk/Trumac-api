<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permission extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::Permission;

    protected $fillable = [
        'is_active',
        'is_deleted',
        'company_id',
        'module_id',
        'name'
    ];
    
  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class);
  }

  public function module(): BelongsTo
  {
    return $this->belongsTo(Module::class);
  }
  
  public function position_permissions(): HasMany
  {
    return $this->hasMany(PositionPermission::class);
  }
}
