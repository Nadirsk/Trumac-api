<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PositionPermission extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::PositionPermission;

    protected $fillable = [
        'is_active',
        'is_deleted',
        'position_id',
        'permission_id',
    ];
    
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
}
