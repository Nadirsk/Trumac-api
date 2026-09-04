<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTimestamp extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::UserTimestamp;

    protected $casts = [
        'old_json' => 'array',
        'new_json' => 'array',
    ];

    protected $fillable = [
        'company_id',
        'user_id',
        'url',
        'name',
        'timespent',
        'old_json',
        'new_json',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->with('roles');
    }
}
