<?php

namespace Database\Seeders;

use App\Models\SalesOrder;
use App\Models\Retailer;
use App\Models\CompanyGodown;
use App\Models\User;
use Illuminate\Database\Seeder;

class SalesOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $retailers = Retailer::where('is_deleted', false)->get();
        $companyGodown = CompanyGodown::where('is_deleted', false)->first();
        $users = User::whereNotNull('position_id')->first();
        $cgBillingUser = User::where('email', 'anjali.cgbilling@gmail.com')->first();

        if ($retailers->isEmpty() || !$users) {
            return;
        }

        $orders = [];
        $orderNumber = 1000;

        foreach ($retailers->take(5) as $retailer) {
            // Delivered order
            $subtotal = rand(5000, 20000);
            $discount = rand(0, 1000);
            $tax = rand(500, 2000);
            $total = $subtotal + $tax - $discount;

            $orders[] = [
                'company_id' => 1,
                'retailer_id' => $retailer->id,
                'order_number' => 'SO-' . str_pad($orderNumber++, 6, '0', STR_PAD_LEFT),
                'source_type' => 'franchise',
                'order_date' => now()->subDays(rand(5, 15)),
                'expected_delivery_date' => now()->subDays(rand(3, 4)),
                'status' => 'delivered',
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'notes' => 'Order delivered successfully',
                'created_by' => $users->id,
                'delivered_by' => $users->id,
                'delivered_at' => now()->subDays(rand(1, 2)),
                'is_deleted' => false,
            ];

            // Pending/Processing order
            $subtotal = rand(5000, 20000);
            $discount = rand(0, 1000);
            $tax = rand(500, 2000);
            $total = $subtotal + $tax - $discount;

            $orders[] = [
                'company_id' => 1,
                'retailer_id' => $retailer->id,
                'order_number' => 'SO-' . str_pad($orderNumber++, 6, '0', STR_PAD_LEFT),
                'source_type' => 'warehouse',
                'order_date' => now()->subDays(rand(1, 3)),
                'expected_delivery_date' => now()->addDays(rand(1, 5)),
                'status' => 'processing',
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'notes' => 'Order being processed',
                'created_by' => $users->id,
                'is_deleted' => false,
            ];
        }

        // Add CG Sales Orders (if company godown exists)
        if ($companyGodown && $cgBillingUser) {
            foreach ($retailers->take(3) as $retailer) {
                // Pending order from Retailer to CG - waiting for approval
                $subtotal = rand(3000, 15000);
                $discount = rand(0, 500);
                $tax = rand(300, 1500);
                $total = $subtotal + $tax - $discount;

                $orders[] = [
                    'company_id' => 1,
                    'retailer_id' => $retailer->id,
                    'order_number' => 'SO-' . str_pad($orderNumber++, 6, '0', STR_PAD_LEFT),
                    'source_type' => 'company_godown',
                    'source_id' => $companyGodown->id,
                    'order_date' => now()->subDays(rand(1, 3)),
                    'expected_delivery_date' => now()->addDays(rand(2, 5)),
                    'status' => 'pending',
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'tax_amount' => $tax,
                    'total_amount' => $total,
                    'notes' => 'Retailer order - waiting for CG Admin approval',
                    'created_by' => $cgBillingUser->id,
                    'is_deleted' => false,
                ];

                // Approved order from Retailer to CG
                $subtotal = rand(5000, 20000);
                $discount = rand(0, 1000);
                $tax = rand(500, 2000);
                $total = $subtotal + $tax - $discount;

                $orders[] = [
                    'company_id' => 1,
                    'retailer_id' => $retailer->id,
                    'order_number' => 'SO-' . str_pad($orderNumber++, 6, '0', STR_PAD_LEFT),
                    'source_type' => 'company_godown',
                    'source_id' => $companyGodown->id,
                    'order_date' => now()->subDays(rand(5, 10)),
                    'expected_delivery_date' => now()->addDays(rand(1, 3)),
                    'status' => 'approved',
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'tax_amount' => $tax,
                    'total_amount' => $total,
                    'notes' => 'Approved by CG Admin - ready for dispatch',
                    'created_by' => $cgBillingUser->id,
                    'is_deleted' => false,
                ];

                // Delivered order from CG to Retailer
                $subtotal = rand(8000, 25000);
                $discount = rand(0, 1500);
                $tax = rand(800, 2500);
                $total = $subtotal + $tax - $discount;

                $orders[] = [
                    'company_id' => 1,
                    'retailer_id' => $retailer->id,
                    'order_number' => 'SO-' . str_pad($orderNumber++, 6, '0', STR_PAD_LEFT),
                    'source_type' => 'company_godown',
                    'source_id' => $companyGodown->id,
                    'order_date' => now()->subDays(rand(10, 20)),
                    'expected_delivery_date' => now()->subDays(rand(5, 8)),
                    'status' => 'delivered',
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'tax_amount' => $tax,
                    'total_amount' => $total,
                    'notes' => 'Delivered by CG Driver - payment pending',
                    'created_by' => $cgBillingUser->id,
                    'delivered_by' => User::where('email', 'sunil.cgdriver@gmail.com')->first()?->id,
                    'delivered_at' => now()->subDays(rand(3, 7)),
                    'is_deleted' => false,
                ];
            }
        }

        foreach ($orders as $order) {
            SalesOrder::firstOrCreate(
                [
                    'order_number' => $order['order_number'],
                ],
                $order
            );
        }
    }
}
