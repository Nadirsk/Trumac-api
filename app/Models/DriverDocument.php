<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DriverDocument extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::DriverDocument;

    protected $fillable = [
        "is_active",
        "is_deleted",
        "company_id",
        "user_id",
        "doc_type_id",
        "doc_front_path",
        "doc_back_path",
        "doc_number",
        "status",
    ];

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)->withTimestamps();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function doc_type(): BelongsTo
    {
        return $this->belongsTo(ValueList::class);
    }
}
