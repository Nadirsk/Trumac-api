<?php

namespace Database\Seeders;

use App\Models\InventoryMovement;
use App\Models\Inventory;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class InventoryMovementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates movement logs for ALL inventory records to explain current stock levels.
     * Uses create() directly (not record()) to avoid modifying already-seeded inventory quantities.
     */
    public function run(): void
    {
        // Clean slate — delete all previous movement records first
        \DB::table('inventory_movements')->delete();

        $adminUser = User::where('email', 'nadirsk@gmail.com')->first()
            ?? User::whereHas('roles', fn($q) => $q->where('name', 'ADMIN'))->first()
            ?? User::first();

        $cgBillingUser = User::where('email', 'anjali.cgbilling@gmail.com')->first() ?? $adminUser;

        if (!$adminUser) {
            return;
        }

        $allInventories = Inventory::where('quantity', '>', 0)->get();

        if ($allInventories->isEmpty()) {
            return;
        }

        /**
         * Logic:
         *   purchase movement quantity = inventory.quantity  (exact match)
         *
         *   So in the UI:
         *     Stock (Total purchased) = inventory.quantity        ← matches movement IN
         *     Reserved                = inventory.reserved_quantity
         *     Available               = inventory.quantity - inventory.reserved_quantity
         *
         *   Example — Surf Excel 1kg @ Head Office:
         *     inventory.quantity          = 1049
         *     inventory.reserved_quantity = 95
         *
         *     Movement IN  (purchase) = 1049   ← stock total
         *     Movement OUT            = 0
         *     Stock shown             = 1049  ✓
         *     Reserved shown          = 95    ✓
         *     Available shown         = 954   ✓  (1049 - 95)
         */
        $movements = [];

        foreach ($allInventories as $inventory) {
            $user = $inventory->location_type === 'company_godown' ? $cgBillingUser : $adminUser;

            // Raw DB value to avoid decimal cast precision issues
            $stockQty = (float) $inventory->getRawOriginal('quantity');

            $movements[] = [
                'company_id'         => $inventory->company_id,
                'sku_id'             => $inventory->sku_id,
                'from_location_type' => 'vendor',
                'from_location_id'   => rand(1, 3),
                'to_location_type'   => $inventory->location_type,
                'to_location_id'     => $inventory->location_id,
                'movement_type'      => 'purchase',
                'quantity'           => $stockQty,   // exactly = inventory.quantity
                'reference_type'     => 'grn',
                'reference_id'       => rand(1, 5),
                'batch_number'       => $inventory->batch_number,
                'notes'              => 'Initial stock received from vendor via GRN',
                'created_by'         => $user->id,
                'created_at'         => now()->subDays(rand(10, 30)),
                'updated_at'         => now()->subDays(rand(10, 30)),
            ];
        }

        // Bulk insert in chunks for performance
        foreach (array_chunk($movements, 100) as $chunk) {
            InventoryMovement::insert($chunk);
        }
    }
}
