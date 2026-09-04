<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Challan extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::Challan;

    protected $fillable = [
        'challan_number',
        'requisition_id',
        'sales_order_id',
        'journey_plan_id',
        'from_location_type',
        'from_location_id',
        'to_location_type',
        'to_location_id',
        'driver_id',
        'vehicle_number',
        'status',
        'dispatched_at',
        'delivered_at',
        'received_by',
        'receiver_signature',
        'delivery_proof_image',
        'notes',
        'delivery_notes',
        'created_by',
        'is_deleted',
        'company_id',
    ];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
        'is_deleted' => 'boolean',
    ];

    // Status constants
    const STATUS_GENERATED = 'generated';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Generate challan number
     */
    public static function generateChallanNumber($companyId): string
    {
        $prefix = 'CH';
        $date = now()->format('Ymd');
        $lastChallan = self::where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastChallan ? (intval(substr($lastChallan->challan_number, -4)) + 1) : 1;

        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Mark as dispatched
     */
    public function dispatch(): void
    {
        $this->update([
            'status' => self::STATUS_IN_TRANSIT,
            'dispatched_at' => now(),
        ]);
    }

    /**
     * Mark as delivered
     */
    public function markDelivered($receivedBy = null, $signature = null, $proofImage = null, $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
            'received_by' => $receivedBy,
            'receiver_signature' => $signature,
            'delivery_proof_image' => $proofImage,
            'delivery_notes' => $notes,
        ]);
    }

    /**
     * Mark as received (GRN created)
     */
    public function markReceived(): void
    {
        $this->update([
            'status' => self::STATUS_RECEIVED,
        ]);
    }

    /**
     * Get the from location model
     */
    public function getFromLocation()
    {
        $modelMap = [
            'warehouse' => Warehouse::class,
            'head_office' => null,
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

    /**
     * Check if challan is fully received
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

    // Relationships
    public function items()
    {
        return $this->hasMany(ChallanItem::class);
    }

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }

    public function journeyPlan()
    {
        return $this->belongsTo(JourneyPlan::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_deleted', false);
    }

    public function scopeForDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', self::STATUS_IN_TRANSIT);
    }

    public function scopePendingDelivery($query)
    {
        return $query->whereIn('status', [self::STATUS_GENERATED, self::STATUS_IN_TRANSIT]);
    }
}
