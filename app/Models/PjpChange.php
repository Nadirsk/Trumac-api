<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PjpChange extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::PjpChange;

    protected $fillable = [
        'is_deleted',
        'company_id',
        'pjp_id',
        'date',
        'action',
        'retailer_id',
        'swap_retailer_id',
        'reason',
        'status',
        'requested_by',
        'approved_by',
        'remarks',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * Action types
     */
    const ACTION_ADD = 'add';
    const ACTION_REMOVE = 'remove';
    const ACTION_SWAP = 'swap';
    const ACTION_REORDER = 'reorder';

    /**
     * Status types
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function pjp(): BelongsTo
    {
        return $this->belongsTo(Pjp::class);
    }

    public function retailer(): BelongsTo
    {
        return $this->belongsTo(Retailer::class);
    }

    public function swapRetailer(): BelongsTo
    {
        return $this->belongsTo(Retailer::class, 'swap_retailer_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if the change can still be modified (only if date is in future)
     */
    public function canModify(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->date->isFuture();
    }

    /**
     * Check if the change request is for tomorrow or later (required 1 day advance)
     */
    public static function isValidChangeDate($date): bool
    {
        $changeDate = \Carbon\Carbon::parse($date);
        $minDate = \Carbon\Carbon::tomorrow();

        return $changeDate->gte($minDate);
    }
}
