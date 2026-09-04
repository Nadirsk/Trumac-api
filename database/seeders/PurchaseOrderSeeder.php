<?php

namespace Database\Seeders;

use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Seeder;

class PurchaseOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vendors = Vendor::where('is_deleted', false)->get();
        $warehouse = Warehouse::where('is_deleted', false)->first();
        $user = User::where('email', 'nadirsk@gmail.com')->first();

        if ($vendors->isEmpty() || !$warehouse || !$user) {
            return;
        }

        $orders = [];
        $poNumber = 1000;

        foreach ($vendors->take(3) as $vendor) {
            // Approved PO
            $subtotal = rand(50000, 200000);
            $discount = rand(0, 5000);
            $tax = rand(5000, 20000);
            $total = $subtotal + $tax - $discount;

            $orders[] = [
                'company_id' => 1,
                'vendor_id' => $vendor->id,
                'destination_type' => 'warehouse',
                'destination_id' => $warehouse->id,
                'po_number' => 'PO-' . str_pad($poNumber++, 6, '0', STR_PAD_LEFT),
                'order_date' => now()->subDays(rand(10, 30)),
                'expected_date' => now()->addDays(rand(5, 15)),
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'status' => 'approved',
                'terms' => '30 Days Credit - Payment due within 30 days of invoice date',
                'notes' => 'Purchase order for warehouse stock replenishment',
                'created_by' => $user->id,
                'approved_by' => User::where('email', 'ADMIN@GMAIL.COM')->first()?->id,
                'approved_at' => now()->subDays(rand(8, 25)),
                'is_deleted' => false,
            ];

            // Pending PO
            $subtotal = rand(50000, 200000);
            $discount = rand(0, 5000);
            $tax = rand(5000, 20000);
            $total = $subtotal + $tax - $discount;

            $orders[] = [
                'company_id' => 1,
                'vendor_id' => $vendor->id,
                'destination_type' => 'warehouse',
                'destination_id' => $warehouse->id,
                'po_number' => 'PO-' . str_pad($poNumber++, 6, '0', STR_PAD_LEFT),
                'order_date' => now()->subDays(rand(1, 5)),
                'expected_date' => now()->addDays(rand(10, 20)),
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'status' => 'pending',
                'terms' => '30 Days Credit',
                'notes' => 'Pending approval from management',
                'created_by' => $user->id,
                'is_deleted' => false,
            ];
        }

        foreach ($orders as $order) {
            PurchaseOrder::firstOrCreate(
                [
                    'po_number' => $order['po_number'],
                ],
                $order
            );
        }
    }
}
