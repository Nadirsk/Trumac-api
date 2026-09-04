<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::Inventory;

    protected $fillable = [
        'sku_id',
        'location_type',
        'location_id',
        'quantity',
        'reserved_quantity',
        'min_quantity',
        'max_quantity',
        'batch_number',
        'expiry_date',
        'manufacturing_date',
        'company_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'reserved_quantity' => 'decimal:2',
        'min_quantity' => 'decimal:2',
        'max_quantity' => 'decimal:2',
        'expiry_date' => 'date',
        'manufacturing_date' => 'date',
    ];

    protected $appends = ['location', 'available_quantity'];

    // Location types
    const LOCATION_HEAD_OFFICE = 'head_office';
    const LOCATION_WAREHOUSE = 'warehouse';
    const LOCATION_GODOWN = 'company_godown';
    const LOCATION_FRANCHISE = 'franchise';

    /**
     * Get available quantity (total - reserved)
     */
    public function getAvailableQuantityAttribute(): float
    {
        return $this->quantity - $this->reserved_quantity;
    }

    /**
     * Check if stock is low
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->min_quantity;
    }

    /**
     * Check if out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
    }

    /**
     * Reserve quantity for an order
     */
    public function reserve(float $qty): bool
    {
        if ($this->available_quantity < $qty) {
            return false;
        }
        $this->reserved_quantity += $qty;
        $this->save();
        return true;
    }

    /**
     * Release reserved quantity
     */
    public function release(float $qty): void
    {
        $this->reserved_quantity = max(0, $this->reserved_quantity - $qty);
        $this->save();
    }

    /**
     * Deduct stock (for sales, transfers out)
     */
    public function deduct(float $qty): bool
    {
        if ($this->quantity < $qty) {
            return false;
        }
        $this->quantity -= $qty;
        $this->save();

        // Check if stock dropped below threshold — trigger out-of-stock notification
        $this->checkOutOfStockAlert();

        return true;
    }

    /**
     * Check if inventory dropped below out-of-stock threshold and notify
     */
    private function checkOutOfStockAlert(): void
    {
        $sku = Sku::find($this->sku_id);
        if (!$sku || !$sku->out_of_stock_threshold) return;

        if ($this->quantity <= $sku->out_of_stock_threshold) {
            $locationRoleMap = ['head_office' => 4, 'warehouse' => 5, 'company_godown' => 6, 'franchise' => 7];
            $targetRoleId = $locationRoleMap[$this->location_type] ?? null;
            if (!$targetRoleId) return;

            $targetUsers = User::whereHas('position', fn($q) => $q->where('role_id', $targetRoleId))
                ->whereHas('companies', fn($q) => $q->where('companies.id', $this->company_id))
                ->pluck('id')->toArray();

            if (!empty($targetUsers)) {
                $locationLabel = ucfirst(str_replace('_', ' ', $this->location_type));
                Notification::sendToMany(
                    $targetUsers,
                    'Out of Stock: ' . ($sku->name ?? 'SKU'),
                    'SKU "' . ($sku->name ?? '') . '" (' . ($sku->code ?? '') . ') has reached threshold at ' . $locationLabel . '. Current qty: ' . $this->quantity . ', Threshold: ' . $sku->out_of_stock_threshold,
                    'out_of_stock',
                    [
                        'sku_id' => $this->sku_id,
                        'inventory_id' => $this->id,
                        'location_type' => $this->location_type,
                        'current_quantity' => $this->quantity,
                        'threshold' => $sku->out_of_stock_threshold,
                    ],
                    $this->company_id
                );
            }
        }
    }

    /**
     * Add stock (for purchases, transfers in)
     */
    public function add(float $qty): void
    {
        $this->quantity += $qty;
        $this->save();
    }

    /**
     * Get or create inventory record
     */
    public static function getOrCreate(int $skuId, string $locationType, int $locationId, ?int $companyId = null): self
    {
        return self::firstOrCreate([
            'sku_id' => $skuId,
            'location_type' => $locationType,
            'location_id' => $locationId,
        ], [
            'quantity' => 0,
            'reserved_quantity' => 0,
            'min_quantity' => 0,
            'company_id' => $companyId,
        ]);
    }

    // Relationships
    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get location attribute (accessor for JSON)
     */
    public function getLocationAttribute()
    {
        if (!$this->location_type || !$this->location_id) {
            return null;
        }

        if ($this->location_type === 'head_office') {
            return (object) ['name' => 'Head Office', 'type' => 'head_office'];
        }

        $modelMap = [
            self::LOCATION_WAREHOUSE => Warehouse::class,
            self::LOCATION_GODOWN => CompanyGodown::class,
            self::LOCATION_FRANCHISE => Franchise::class,
        ];

        $modelClass = $modelMap[$this->location_type] ?? null;
        if ($modelClass) {
            return $modelClass::find($this->location_id);
        }

        return null;
    }

    /**
     * Get the location model (polymorphic) - Legacy method
     */
    public function getLocation()
    {
        return $this->location;
    }

    // Scopes
    public function scopeAtLocation($query, string $locationType, int $locationId)
    {
        return $query->where('location_type', $locationType)
            ->where('location_id', $locationId);
    }

    public function scopeLowStock($query)
    {
        // Low stock: quantity > 0 but below SKU's out_of_stock_threshold
        return $query->where('quantity', '>', 0)
            ->whereHas('sku', function ($q) {
                $q->whereColumn('inventories.quantity', '<=', 'skus.out_of_stock_threshold')
                    ->where('skus.out_of_stock_threshold', '>', 0);
            });
    }

    public function scopeOutOfStock($query)
    {
        // Out of stock: quantity is 0 or below
        return $query->where('quantity', '<=', 0);
    }

    public function scopeForSku($query, $skuId)
    {
        return $query->where('sku_id', $skuId);
    }
}
