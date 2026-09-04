<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetailerRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'retailer_id',
        'rated_by',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
    ];

    public function retailer(): BelongsTo
    {
        return $this->belongsTo(Retailer::class);
    }

    public function ratedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_by');
    }

    /**
     * Auto-update retailer average rating after save
     */
    protected static function booted(): void
    {
        static::saved(function (RetailerRating $rating) {
            $rating->retailer->updateAverageRating();
        });

        static::deleted(function (RetailerRating $rating) {
            $rating->retailer->updateAverageRating();
        });
    }
}
