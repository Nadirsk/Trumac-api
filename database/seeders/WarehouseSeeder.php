<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use App\Models\Location;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            [
                'company_id' => 1,
                'location_id' => Location::where('name', 'Mumbai Central')->first()?->id,
                'name' => 'Mumbai Main Warehouse',
                'code' => 'WH-MUM-001',
                'contact_person' => 'Rajesh Sharma',
                'phone' => '9876543210',
                'email' => 'mumbai.warehouse@trumac.com',
                'capacity' => 5000,
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'company_id' => 1,
                'location_id' => Location::where('name', 'Pune Branch')->first()?->id,
                'name' => 'Pune Warehouse',
                'code' => 'WH-PUN-001',
                'contact_person' => 'Amit Deshmukh',
                'phone' => '9876543211',
                'email' => 'pune.warehouse@trumac.com',
                'capacity' => 3000,
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'company_id' => 1,
                'location_id' => Location::where('name', 'Thane Warehouse')->first()?->id,
                'name' => 'Thane Warehouse',
                'code' => 'WH-THA-001',
                'contact_person' => 'Suresh Patil',
                'phone' => '9876543212',
                'email' => 'thane.warehouse@trumac.com',
                'capacity' => 2500,
                'is_active' => true,
                'is_deleted' => false,
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::firstOrCreate(
                [
                    'code' => $warehouse['code'],
                    'company_id' => $warehouse['company_id'],
                ],
                $warehouse
            );
        }
    }
}
