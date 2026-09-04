<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Requisition;

class Grn extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::Grn;

    protected $fillable = [
        'grn_number',
        'reference_type',
        'reference_id',
        'location_type',
        'location_id',
        'received_by',
        'received_date',
        'status',
        'notes',
        'vehicle_number',
        'driver_name',
        'is_deleted',
        'company_id',
    ];

    protected $casts = [
        'received_date' => 'date',
        'is_deleted' => 'boolean',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // Reference types
    const REF_PURCHASE_ORDER = 'purchase_order';
    const REF_REQUISITION = 'requisition';
    const REF_RETURN = 'return';

    /**
     * Generate GRN number
     */
    public static function generateGrnNumber($companyId): string
    {
        $prefix = 'GRN';
        $date = now()->format('Ymd');
        $lastGrn = self::where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastGrn ? (intval(substr($lastGrn->grn_number, -4)) + 1) : 1;

        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the reference model
     */
    public function getReference()
    {
        $modelMap = [
            self::REF_PURCHASE_ORDER => PurchaseOrder::class,
            self::REF_REQUISITION => Requisition::class,
        ];

        $modelClass = $modelMap[$this->reference_type] ?? null;
        if ($modelClass) {
            return $modelClass::find($this->reference_id);
        }

        return null;
    }

    /**
     * Complete the GRN and update inventory
     */
    public function complete($userId = null): void
    {
        foreach ($this->items as $item) {
            if ($item->received_quantity > 0) {
                // Add to inventory
                $inventory = Inventory::getOrCreate(
                    $item->sku_id,
                    $this->location_type,
                    $this->location_id ?? 0,
                    $this->company_id
                );
                $inventory->add($item->received_quantity);

                // Record movement
                InventoryMovement::create([
                    'sku_id' => $item->sku_id,
                    'to_location_type' => $this->location_type,
                    'to_location_id' => $this->location_id,
                    'quantity' => $item->received_quantity,
                    'movement_type' => InventoryMovement::TYPE_PURCHASE,
                    'reference_type' => 'grn',
                    'reference_id' => $this->id,
                    'batch_number' => $item->batch_number,
                    'created_by' => $userId,
                    'company_id' => $this->company_id,
                ]);

                // Update item status
                $item->status = $item->received_quantity >= $item->expected_quantity ? 'received' : 'partial';
                $item->save();
            }
        }

        // Update PO received quantities if reference is purchase order
        if ($this->reference_type === self::REF_PURCHASE_ORDER) {
            $po = PurchaseOrder::find($this->reference_id);
            if ($po) {
                foreach ($this->items as $grnItem) {
                    $poItem = $po->items()->where('sku_id', $grnItem->sku_id)->first();
                    if ($poItem) {
                        $poItem->received_quantity += $grnItem->received_quantity;
                        $poItem->save();
                    }
                }
                $po->updateReceivedStatus();
            }
        }

        $this->status = self::STATUS_COMPLETED;
        $this->received_by = $userId;
        $this->save();

        // Auto-generate Purchase Invoice and mark requisition as received
        if ($this->reference_type === self::REF_REQUISITION) {
            PurchaseInvoice::createFromGrn($this);

            $requisition = Requisition::find($this->reference_id);
            if ($requisition) {
                $requisition->update(['status' => Requisition::STATUS_RECEIVED]);
            }
        }
    }

    // Relationships
    public function items()
    {
        return $this->hasMany(GrnItem::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'reference_id')
            ->where('reference_type', self::REF_PURCHASE_ORDER);
    }

    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id')->withDefault();
    }

    public function requisition()
    {
        return $this->belongsTo(Requisition::class, 'reference_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_deleted', false);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }
}
