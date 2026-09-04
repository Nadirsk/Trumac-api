<?php

namespace Database\Seeders;

use App\Models\SalesOrderItem;
use App\Models\SalesOrder;
use App\Models\Sku;
use Illuminate\Database\Seeder;

class SalesOrderItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salesOrders = SalesOrder::where('is_deleted', false)->get();
        $skus = Sku::where('is_deleted', false)->get();

        if ($salesOrders->isEmpty() || $skus->isEmpty()) {
            return;
        }

        $items = [];

        foreach ($salesOrders as $order) {
            $selectedSkus = $skus->random(min(3, $skus->count()));

            foreach ($selectedSkus as $sku) {
                $quantity = rand(5, 50);
                $unitPrice = rand(100, 1000);
                $discountPercent = rand(0, 10);
                $taxPercent = 12; // GST 12%

                $discountAmount = ($quantity * $unitPrice * $discountPercent) / 100;
                $subtotal = ($quantity * $unitPrice) - $discountAmount;
                $taxAmount = ($subtotal * $taxPercent) / 100;
                $total = $subtotal + $taxAmount;

                $items[] = [
                    'sales_order_id' => $order->id,
                    'sku_id' => $sku->id,
                    'quantity' => $quantity,
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
            SalesOrderItem::create($item);
        }
    }
}
