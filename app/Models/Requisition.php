<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Requisition extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::Requisition;

    protected $fillable = [
        'requisition_number',
        'from_location_type',
        'from_location_id',
        'to_location_type',
        'to_location_id',
        'status',
        'request_date',
        'required_date',
        'requested_by',
        'approved_by',
        'approved_at',
        'notes',
        'rejection_reason',
        'driver_id',
        'vehicle_number',
        'fulfillment_notes',
        'journey_plan_id',
        'is_deleted',
        'company_id',
    ];

    protected $casts = [
        'request_date' => 'date',
        'required_date' => 'date',
        'approved_at' => 'datetime',
        'is_deleted' => 'boolean',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_PARTIAL = 'partial';
    const STATUS_FULFILLED = 'fulfilled';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Generate requisition number
     */
    public static function generateRequisitionNumber($companyId): string
    {
        $prefix = 'REQ';
        $date = now()->format('Ymd');
        $lastReq = self::where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastReq ? (intval(substr($lastReq->requisition_number, -4)) + 1) : 1;

        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if requisition can be approved
     */
    public function canApprove(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    /**
     * Approve requisition
     */
    public function approve($userId, array $approvedQuantities = []): void
    {
        // Update approved quantities if provided
        if (!empty($approvedQuantities)) {
            foreach ($approvedQuantities as $itemId => $qty) {
                $item = $this->items()->find($itemId);
                if ($item) {
                    $item->approved_quantity = $qty;
                    $item->save();
                }
            }
        } else {
            // Default: approve all requested quantities
            foreach ($this->items as $item) {
                $item->approved_quantity = $item->requested_quantity;
                $item->save();
            }
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject requisition
     */
    public function reject($reason): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Check if fully fulfilled
     */
    public function isFullyFulfilled(): bool
    {
        foreach ($this->items as $item) {
            if ($item->fulfilled_quantity < $item->approved_quantity) {
                return false;
            }
        }
        return true;
    }

    /**
     * Update fulfillment status
     */
    public function updateFulfillmentStatus(): void
    {
        if ($this->isFullyFulfilled()) {
            $this->status = self::STATUS_FULFILLED;
        } elseif ($this->items->sum('fulfilled_quantity') > 0) {
            $this->status = self::STATUS_PARTIAL;
        }
        $this->save();
    }

    /**
     * Get the from location model
     */
    public function getFromLocation()
    {
        $modelMap = [
            'warehouse' => Warehouse::class,
            'head_office' => null, // HO is the company itself
        ];

        if ($this->from_location_type === 'head_office') {
            return (object)['name' => 'Head Office', 'id' => 0];
        }

        $modelClass = $modelMap[$this->from_location_type] ?? null;
        if ($modelClass) {
            return $modelClass::find($this->from_location_id);
        }

        return null;
    }

    /**
     * Get the to location model
     */
    public function getToLocation()
    {
        $modelMap = [
            'warehouse' => Warehouse::class,
            'company_godown' => CompanyGodown::class,
            'franchise' => Franchise::class,
        ];

        $modelClass = $modelMap[$this->to_location_type] ?? null;
        if ($modelClass) {
            return $modelClass::find($this->to_location_id);
        }

        return null;
    }

    // Relationships
    public function items()
    {
        return $this->hasMany(RequisitionItem::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function journeyPlan()
    {
        return $this->belongsTo(JourneyPlan::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function challans()
    {
        return $this->hasMany(Challan::class);
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

    public function scopeFromLocation($query, $type, $id = null)
    {
        $query->where('from_location_type', $type);
        if ($id) {
            $query->where('from_location_id', $id);
        }
        return $query;
    }

    public function scopeToLocation($query, $type, $id)
    {
        return $query->where('to_location_type', $type)
            ->where('to_location_id', $id);
    }
}
