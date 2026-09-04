<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::PurchaseOrder;

    protected $fillable = [
        'po_number',
        'vendor_id',
        'requisition_id',
        'order_date',
        'expected_date',
        'status',
        'destination_type',
        'destination_id',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'notes',
        'terms',
        'created_by',
        'approved_by',
        'approved_at',
        'is_deleted',
        'company_id',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'is_deleted' => 'boolean',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_PARTIAL = 'partial';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Generate PO number
     */
    public static function generatePoNumber($companyId): string
    {
        $prefix = 'PO';
        $date = now()->format('Ymd');
        $lastOrder = self::where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastOrder ? (intval(substr($lastOrder->po_number, -4)) + 1) : 1;

        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate totals
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });

        $taxAmount = $this->items->sum('tax_amount');
        $discountAmount = $this->items->sum('discount_amount');

        $this->subtotal = $subtotal;
        $this->tax_amount = $taxAmount;
        $this->discount_amount = $discountAmount;
        $this->total_amount = $subtotal + $taxAmount - $discountAmount;
        $this->save();
    }

    /**
     * Check if PO can be approved
     */
    public function canApprove(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    /**
     * Approve the PO
     */
    public function approve($userId): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Check if fully received
     */
    public function isFullyReceived(): bool
    {
        foreach ($this->items as $item) {
            if ($item->received_quantity < $item->quantity) {
                return false;
            }
        }
        return true;
    }

    /**
     * Update status based on received quantities
     */
    public function updateReceivedStatus(): void
    {
        if ($this->isFullyReceived()) {
            $this->status = self::STATUS_RECEIVED;
        } elseif ($this->items->sum('received_quantity') > 0) {
            $this->status = self::STATUS_PARTIAL;
        }
        $this->save();
    }

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }

    public function grns()
    {
        return $this->hasMany(Grn::class, 'reference_id')
            ->where('reference_type', 'purchase_order');
    }

    /**
     * Get destination details based on type
     * Note: Destination is loaded manually in controller, not as a relationship
     */
    public function getDestinationName()
    {
        if ($this->destination_type === 'head_office') {
            return $this->company->name ?? 'Head Office';
        }

        return $this->destination->name ?? ucfirst(str_replace('_', ' ', $this->destination_type ?? 'Unknown'));
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_deleted', false);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }
}
