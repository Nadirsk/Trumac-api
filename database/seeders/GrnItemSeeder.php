<?php

namespace Database\Seeders;

use App\Models\GrnItem;
use App\Models\Grn;
use App\Models\PurchaseOrderItem;
use App\Models\RequisitionItem;
use Illuminate\Database\Seeder;

class GrnItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grns = Grn::where('is_deleted', false)->get();

        if ($grns->isEmpty()) {
            return;
        }

        $items = [];

        foreach ($grns as $grn) {
            // Check reference type - GRN can be from Purchase Order or Requisition
            if ($grn->reference_type === 'purchase_order') {
                // Get purchase order items for this GRN's reference
                $poItems = PurchaseOrderItem::where('purchase_order_id', $grn->reference_id)->get();

                foreach ($poItems->take(5) as $poItem) {
                    $expectedQty = $poItem->quantity;
                    $receivedQty = rand(floor($expectedQty * 0.9), $expectedQty);
                    $rejectedQty = rand(0, floor($receivedQty * 0.1)); // 0-10% rejection

                    $items[] = [
                        'grn_id' => $grn->id,
                        'sku_id' => $poItem->sku_id,
                        'expected_quantity' => $expectedQty,
                        'received_quantity' => $receivedQty,
                        'rejected_quantity' => $rejectedQty,
                        'batch_number' => 'BATCH-' . strtoupper(substr(md5(rand()), 0, 8)),
                        'expiry_date' => now()->addMonths(rand(12, 36)),
                        'manufacturing_date' => now()->subMonths(rand(1, 6)),
                        'status' => $rejectedQty > 0 ? 'partial' : 'received',
                        'notes' => $rejectedQty > 0 ? 'Some items damaged or defective - rejected ' . $rejectedQty . ' units' : 'All items received in good condition',
                    ];
                }
            } elseif ($grn->reference_type === 'requisition') {
                // Get requisition items for CG GRN
                $reqItems = RequisitionItem::where('requisition_id', $grn->reference_id)->get();

                foreach ($reqItems->take(5) as $reqItem) {
                    $expectedQty = $reqItem->approved_quantity;

                    // For CG GRNs: Adjust received quantity based on GRN status
                    if ($grn->status === 'completed') {
                        $receivedQty = rand(floor($expectedQty * 0.9), $expectedQty);
                    } elseif ($grn->status === 'draft') {
                        $receivedQty = 0; // Draft GRN - not yet received
                    } else {
                        $receivedQty = 0; // Cancelled GRN
                    }

                    $rejectedQty = $receivedQty > 0 ? rand(0, floor($receivedQty * 0.05)) : 0; // 0-5% rejection for CG

                    $items[] = [
                        'grn_id' => $grn->id,
                        'sku_id' => $reqItem->sku_id,
                        'expected_quantity' => $expectedQty,
                        'received_quantity' => $receivedQty,
                        'rejected_quantity' => $rejectedQty,
                        'batch_number' => 'CG-BATCH-' . strtoupper(substr(md5(rand()), 0, 8)),
                        'expiry_date' => now()->addMonths(rand(12, 36)),
                        'manufacturing_date' => now()->subMonths(rand(1, 6)),
                        'status' => $grn->status === 'completed' ? ($rejectedQty > 0 ? 'partial' : 'received') : ($grn->status === 'draft' ? 'pending' : 'rejected'),
                        'notes' => $grn->status === 'draft' ? 'Awaiting delivery from warehouse' : ($grn->status === 'completed' ? ($rejectedQty > 0 ? 'Received with minor damages - rejected ' . $rejectedQty . ' units' : 'All items received in excellent condition') : 'GRN cancelled'),
                    ];
                }
            }
        }

        foreach ($items as $item) {
            GrnItem::create($item);
        }
    }
}
