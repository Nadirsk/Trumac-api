<?php

namespace Database\Seeders;

use App\Models\Retailer;
use App\Models\Franchise;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;

class RetailerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $franchise1 = Franchise::where('code', 'FR-MUM-E-001')->first();
        $franchise2 = Franchise::where('code', 'FR-NMU-001')->first();
        $franchise3 = Franchise::where('code', 'FR-PUN-C-001')->first();
        $location1 = Location::where('name', 'Mumbai Central')->first();
        $location2 = Location::where('name', 'Navi Mumbai Office')->first();
        $createdBy = User::where('email', 'nadirsk@gmail.com')->first();

        $retailers = [
            [
                'company_id' => 1,
                'franchise_id' => $franchise1?->id,
                'location_id' => $location1?->id,
                'name' => 'Rajesh Medical Store',
                'shop_name' => 'Rajesh Medical & General',
                'phone' => '9876550001',
                'email' => 'rajesh.medical@gmail.com',
                'address' => 'Shop 15, Kurla West, Mumbai',
                'latitude' => 19.0659,
                'longitude' => 72.8817,
                'gst_no' => '27RRRR0001R1Z5',
                'rating' => 4.5,
                'is_flagged' => false,
                'credit_limit' => 0.00,
                'outstanding_amount' => 0.00,
                'created_by' => $createdBy?->id,
                'last_order_date' => now()->subDays(5),
                'total_orders' => 25,
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'company_id' => 1,
                'franchise_id' => $franchise1?->id,
                'location_id' => $location1?->id,
                'name' => 'Sai Pharmacy',
                'shop_name' => 'Sai Pharmacy & Wellness',
                'phone' => '9876550002',
                'email' => 'sai.pharmacy@gmail.com',
                'address' => 'Near Station Road, Ghatkopar, Mumbai',
                'latitude' => 19.0868,
                'longitude' => 72.9081,
                'gst_no' => '27RRRR0002R1Z5',
                'rating' => 4.8,
                'is_flagged' => false,
                'credit_limit' => 0.00,
                'outstanding_amount' => 0.00,
                'created_by' => $createdBy?->id,
                'last_order_date' => now()->subDays(2),
                'total_orders' => 45,
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'company_id' => 1,
                'franchise_id' => $franchise2?->id,
                'location_id' => $location2?->id,
                'name' => 'Apollo Medical',
                'shop_name' => 'Apollo Medical Center',
                'phone' => '9876550003',
                'email' => 'apollo.medical@gmail.com',
                'address' => 'Sector 7, Kharghar, Navi Mumbai',
                'latitude' => 19.0456,
                'longitude' => 73.0674,
                'gst_no' => '27RRRR0003R1Z5',
                'rating' => 4.2,
                'is_flagged' => false,
                'credit_limit' => 0.00,
                'outstanding_amount' => 0.00,
                'created_by' => $createdBy?->id,
                'last_order_date' => now()->subDays(7),
                'total_orders' => 30,
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'company_id' => 1,
                'franchise_id' => $franchise3?->id,
                'location_id' => Location::where('name', 'Pune Branch')->first()?->id,
                'name' => 'Ganesh Medical',
                'shop_name' => 'Ganesh Medical & Surgicals',
                'phone' => '9876550004',
                'email' => 'ganesh.medical@gmail.com',
                'address' => 'JM Road, Shivaji Nagar, Pune',
                'latitude' => 18.5304,
                'longitude' => 73.8464,
                'gst_no' => '27RRRR0004R1Z5',
                'rating' => 4.6,
                'is_flagged' => false,
                'credit_limit' => 0.00,
                'outstanding_amount' => 0.00,
                'created_by' => $createdBy?->id,
                'last_order_date' => now()->subDays(3),
                'total_orders' => 50,
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'company_id' => 1,
                'franchise_id' => $franchise1?->id,
                'location_id' => $location1?->id,
                'name' => 'Lifeline Pharmacy',
                'shop_name' => 'Lifeline 24x7 Pharmacy',
                'phone' => '9876550005',
                'email' => 'lifeline.pharmacy@gmail.com',
                'address' => 'Andheri East, Mumbai',
                'latitude' => 19.1197,
                'longitude' => 72.8464,
                'gst_no' => '27RRRR0005R1Z5',
                'rating' => 3.9,
                'is_flagged' => true,
                'credit_limit' => 0.00,
                'outstanding_amount' => 0.00,
                'created_by' => $createdBy?->id,
                'last_order_date' => now()->subDays(15),
                'total_orders' => 18,
                'is_active' => true,
                'is_deleted' => false,
            ],
        ];

        foreach ($retailers as $retailer) {
            Retailer::firstOrCreate(
                [
                    'phone' => $retailer['phone'],
                    'company_id' => $retailer['company_id'],
                ],
                $retailer
            );
        }
    }
}
