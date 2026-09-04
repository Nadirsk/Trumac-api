<?php

namespace Database\Seeders;

use App\Models\CompanyGodown;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class CompanyGodownSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $godowns = [
            [
                'company_id' => 1,
                'warehouse_id' => Warehouse::where('code', 'WH-MUM-001')->first()?->id,
                'location_id' => null,
                'name' => 'Godown A - Cold Storage',
                'code' => 'GD-MUM-A',
                'contact_person' => 'Ramesh Patil',
                'phone' => '9876540100',
                'capacity' => 1000,
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'company_id' => 1,
                'warehouse_id' => Warehouse::where('code', 'WH-MUM-001')->first()?->id,
                'location_id' => null,
                'name' => 'Godown B - General Storage',
                'code' => 'GD-MUM-B',
                'contact_person' => 'Suresh Kumar',
                'phone' => '9876540101',
                'capacity' => 2000,
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'company_id' => 1,
                'warehouse_id' => Warehouse::where('code', 'WH-PUN-001')->first()?->id,
                'location_id' => null,
                'name' => 'Godown A - General Storage',
                'code' => 'GD-PUN-A',
                'contact_person' => 'Vijay Deshmukh',
                'phone' => '9876540102',
                'capacity' => 1500,
                'is_active' => true,
                'is_deleted' => false,
            ],
        ];

        foreach ($godowns as $godown) {
            CompanyGodown::firstOrCreate(
                [
                    'code' => $godown['code'],
                    'company_id' => $godown['company_id'],
                ],
                $godown
            );
        }
    }
}
