<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Sku;
use App\Models\Warehouse;
use App\Models\CompanyGodown;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $skus = Sku::where('is_deleted', false)->get();
        $warehouses = Warehouse::where('is_deleted', false)->get();

        if ($skus->isEmpty() || $warehouses->isEmpty()) {
            return;
        }

        $inventories = [];
        $stockScenarios = ['out_of_stock', 'low_stock', 'normal_stock'];

        foreach ($warehouses as $warehouse) {
            $skuIndex = 0;
            foreach ($skus->take(15) as $sku) {
                // Cycle through different stock scenarios
                $scenario = $stockScenarios[$skuIndex % 3];
                $minQty = rand(20, 100);

                // Set quantity based on scenario
                switch ($scenario) {
                    case 'out_of_stock':
                        $totalQty = 0;
                        $reservedQty = 0;
                        break;
                    case 'low_stock':
                        // Quantity at or below min_quantity (1-100% of min)
                        $totalQty = rand(1, $minQty);
                        $reservedQty = 0; // No reservations for low stock
                        break;
                    default: // normal_stock
                        $totalQty = rand(100, 1000);
                        $reservedQty = rand(0, intval($totalQty * 0.2)); // 0-20% reserved
                }

                $inventories[] = [
                    'company_id' => 1,
                    'sku_id' => $sku->id,
                    'location_type' => 'warehouse',
                    'location_id' => $warehouse->id,
                    'quantity' => $totalQty,
                    'reserved_quantity' => $reservedQty,
                    'min_quantity' => $minQty,
                    'max_quantity' => rand(500, 1500),
                    'batch_number' => 'BATCH-' . strtoupper(substr(md5(rand()), 0, 8)),
                    'manufacturing_date' => now()->subMonths(rand(1, 6)),
                    'expiry_date' => now()->addMonths(rand(12, 24)),
                ];

                $skuIndex++;
            }
        }

        foreach ($inventories as $inventory) {
            Inventory::firstOrCreate(
                [
                    'sku_id' => $inventory['sku_id'],
                    'location_type' => $inventory['location_type'],
                    'location_id' => $inventory['location_id'],
                ],
                $inventory
            );
        }

        // Seed Head Office inventory
        $this->seedHeadOfficeInventory($skus);

        // Seed Company Godown inventory
        $this->seedCompanyGodownInventory($skus);
    }

    /**
     * Seed inventory for Head Office
     */
    private function seedHeadOfficeInventory($skus): void
    {
        $stockScenarios = ['normal_stock', 'normal_stock', 'low_stock'];
        $skuIndex = 0;

        foreach ($skus as $sku) {
            $scenario = $stockScenarios[$skuIndex % 3];
            $minQty = rand(50, 200);

            switch ($scenario) {
                case 'low_stock':
                    $totalQty = rand(1, $minQty);
                    $reservedQty = 0;
                    break;
                default: // normal_stock - HO should have higher stock
                    $totalQty = rand(500, 5000);
                    $reservedQty = rand(0, intval($totalQty * 0.1));
            }

            Inventory::firstOrCreate(
                [
                    'sku_id' => $sku->id,
                    'location_type' => 'head_office',
                    'location_id' => 0,
                ],
                [
                    'company_id' => 1,
                    'sku_id' => $sku->id,
                    'location_type' => 'head_office',
                    'location_id' => 0,
                    'quantity' => $totalQty,
                    'reserved_quantity' => $reservedQty,
                    'min_quantity' => $minQty,
                    'max_quantity' => rand(5000, 10000),
                    'batch_number' => 'HO-BATCH-' . strtoupper(substr(md5(rand()), 0, 8)),
                    'manufacturing_date' => now()->subMonths(rand(1, 6)),
                    'expiry_date' => now()->addMonths(rand(12, 24)),
                ]
            );

            $skuIndex++;
        }
    }

    /**
     * Seed inventory for Company Godowns
     */
    private function seedCompanyGodownInventory($skus): void
    {
        $companyGodowns = CompanyGodown::where('is_deleted', false)->get();

        if ($companyGodowns->isEmpty()) {
            return;
        }

        $stockScenarios = ['out_of_stock', 'low_stock', 'normal_stock'];

        foreach ($companyGodowns as $godown) {
            $skuIndex = 0;
            foreach ($skus->take(20) as $sku) {
                $scenario = $stockScenarios[$skuIndex % 3];
                $minQty = rand(10, 50);

                // Set quantity based on scenario
                switch ($scenario) {
                    case 'out_of_stock':
                        $totalQty = 0;
                        $reservedQty = 0;
                        break;
                    case 'low_stock':
                        $totalQty = rand(1, $minQty);
                        $reservedQty = 0;
                        break;
                    default: // normal_stock
                        $totalQty = rand(50, 500);
                        $reservedQty = rand(0, intval($totalQty * 0.15));
                }

                Inventory::firstOrCreate(
                    [
                        'sku_id' => $sku->id,
                        'location_type' => 'company_godown',
                        'location_id' => $godown->id,
                    ],
                    [
                        'company_id' => 1,
                        'sku_id' => $sku->id,
                        'location_type' => 'company_godown',
                        'location_id' => $godown->id,
                        'quantity' => $totalQty,
                        'reserved_quantity' => $reservedQty,
                        'min_quantity' => $minQty,
                        'max_quantity' => rand(200, 800),
                        'batch_number' => 'CG-BATCH-' . strtoupper(substr(md5(rand()), 0, 8)),
                        'manufacturing_date' => now()->subMonths(rand(1, 6)),
                        'expiry_date' => now()->addMonths(rand(12, 24)),
                    ]
                );

                $skuIndex++;
            }
        }
    }
}
