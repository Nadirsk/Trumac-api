<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku_id',
        'from_location_type',
        'from_location_id',
        'to_location_type',
        'to_location_id',
        'quantity',
        'movement_type',
        'reference_type',
        'reference_id',
        'batch_number',
        'notes',
        'created_by',
        'company_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    protected $appends = ['from_location', 'to_location'];

    // Movement types
    const TYPE_PURCHASE = 'purchase';
    const TYPE_SALE = 'sale';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_RETURN = 'return';
    const TYPE_DAMAGE = 'damage';
    const TYPE_EXPIRY = 'expiry';

    /**
     * Record an inventory movement and update stock
     */
    public static function record(array $data, bool $updateInventory = true): self
    {
        $movement = self::create($data);

        if ($updateInventory) {
            // Deduct from source
            if ($data['from_location_type'] && $data['from_location_id']) {
                $fromInventory = Inventory::getOrCreate(
                    $data['sku_id'],
                    $data['from_location_type'],
                    $data['from_location_id'],
                    $data['company_id'] ?? null
                );
                $fromInventory->deduct($data['quantity']);
            }

            // Add to destination
            if ($data['to_location_type'] && $data['to_location_id']) {
                $toInventory = Inventory::getOrCreate(
                    $data['sku_id'],
                    $data['to_location_type'],
                    $data['to_location_id'],
                    $data['company_id'] ?? null
                );
                $toInventory->add($data['quantity']);
            }
        }

        return $movement;
    }

    // Relationships
    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get source location (accessor for from_location attribute)
     */
    public function getFromLocationAttribute()
    {
        if (!$this->from_location_type || !$this->from_location_id) {
            return null;
        }

        $modelMap = [
            'warehouse' => Warehouse::class,
            'company_godown' => CompanyGodown::class,
            'franchise' => Franchise::class,
            'vendor' => Vendor::class,
        ];

        $modelClass = $modelMap[$this->from_location_type] ?? null;
        if ($modelClass) {
            return $modelClass::find($this->from_location_id);
        }

        return null;
    }

    /**
     * Get destination location (accessor for to_location attribute)
     */
    public function getToLocationAttribute()
    {
        if (!$this->to_location_type || !$this->to_location_id) {
            return null;
        }

        $modelMap = [
            'warehouse' => Warehouse::class,
            'company_godown' => CompanyGodown::class,
            'franchise' => Franchise::class,
            'retailer' => Retailer::class,
        ];

        $modelClass = $modelMap[$this->to_location_type] ?? null;
        if ($modelClass) {
            return $modelClass::find($this->to_location_id);
        }

        return null;
    }

    // Scopes
    public function scopeForSku($query, $skuId)
    {
        return $query->where('sku_id', $skuId);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('movement_type', $type);
    }

    public function scopeAtLocation($query, string $locationType, int $locationId)
    {
        return $query->where(function ($q) use ($locationType, $locationId) {
            $q->where(function ($sub) use ($locationType, $locationId) {
                $sub->where('from_location_type', $locationType)
                    ->where('from_location_id', $locationId);
            })->orWhere(function ($sub) use ($locationType, $locationId) {
                $sub->where('to_location_type', $locationType)
                    ->where('to_location_id', $locationId);
            });
        });
    }
}
