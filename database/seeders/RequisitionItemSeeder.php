<?php

namespace Database\Seeders;

use App\Models\RequisitionItem;
use App\Models\Requisition;
use App\Models\Sku;
use Illuminate\Database\Seeder;

class RequisitionItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $requisitions = Requisition::where('is_deleted', false)->get();
        $skus = Sku::where('is_deleted', false)->get();

        if ($requisitions->isEmpty() || $skus->isEmpty()) {
            return;
        }

        $items = [];

        foreach ($requisitions as $requisition) {
            $selectedSkus = $skus->random(min(5, $skus->count()));

            foreach ($selectedSkus as $sku) {
                $requestedQty = rand(50, 200);
                $approvedQty = $requisition->status === 'approved' ? rand(floor($requestedQty * 0.8), $requestedQty) : 0;
                $fulfilledQty = $approvedQty > 0 ? rand(0, $approvedQty) : 0;

                $items[] = [
                    'requisition_id' => $requisition->id,
                    'sku_id' => $sku->id,
                    'requested_quantity' => $requestedQty,
                    'approved_quantity' => $approvedQty,
                    'fulfilled_quantity' => $fulfilledQty,
                    'notes' => $approvedQty < $requestedQty ? 'Partial approval - stock limitations' : ($fulfilledQty < $approvedQty ? 'Partially fulfilled' : 'Fully approved and fulfilled'),
                ];
            }
        }

        foreach ($items as $item) {
            RequisitionItem::create($item);
        }
    }
}
