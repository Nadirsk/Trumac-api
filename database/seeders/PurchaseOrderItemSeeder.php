<?php

namespace Database\Seeders;

use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrder;
use App\Models\Sku;
use Illuminate\Database\Seeder;

class PurchaseOrderItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $purchaseOrders = PurchaseOrder::where('is_deleted', false)->get();
        $skus = Sku::where('is_deleted', false)->get();

        if ($purchaseOrders->isEmpty() || $skus->isEmpty()) {
            return;
        }

        $items = [];

        foreach ($purchaseOrders as $order) {
            $selectedSkus = $skus->random(min(5, $skus->count()));

            foreach ($selectedSkus as $sku) {
                $quantity = rand(50, 500);
                $unitPrice = rand(100, 1000);
                $discountPercent = rand(0, 5);
                $taxPercent = 12; // GST 12%

                $discountAmount = ($quantity * $unitPrice * $discountPercent) / 100;
                $subtotal = ($quantity * $unitPrice) - $discountAmount;
                $taxAmount = ($subtotal * $taxPercent) / 100;
                $total = $subtotal + $taxAmount;

                $items[] = [
                    'purchase_order_id' => $order->id,
                    'sku_id' => $sku->id,
                    'quantity' => $quantity,
                    'received_quantity' => $order->status === 'approved' ? rand(0, $quantity) : 0,
                    'unit_price' => $unitPrice,
                    'discount_percent' => $discountPercent,
                    'discount_amount' => $discountAmount,
                    'tax_percent' => $taxPercent,
                    'tax_amount' => $taxAmount,
                    'total' => $total,
                ];
            }
        }

        foreach ($items as $item) {
            PurchaseOrderItem::create($item);
        }
    }
}
