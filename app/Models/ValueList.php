<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValueList extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::ValueList;

    protected $fillable = [
        'is_active',
        'is_deleted',
        'company_id',
        'value_id',
        'description',
        'code',
    ];
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function value(): BelongsTo
    {
        return $this->belongsTo(Value::class);
    }
}
