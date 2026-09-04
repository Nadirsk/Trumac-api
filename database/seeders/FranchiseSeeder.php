<?php

namespace Database\Seeders;

use App\Models\Franchise;
use App\Models\Warehouse;
use App\Models\Location;
use Illuminate\Database\Seeder;

class FranchiseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $franchises = [
            [
                'company_id' => 1,
                'warehouse_id' => Warehouse::where('code', 'WH-MUM-001')->first()?->id,
                'location_id' => Location::where('name', 'Mumbai Central')->first()?->id,
                'name' => 'Mumbai East Franchise',
                'code' => 'FR-MUM-E-001',
                'owner_name' => 'Anil Kapoor',
                'phone' => '9876540001',
                'email' => 'anil.franchise@trumac.com',
                'gst_no' => '27FFFFF0001F1Z5',
                'pan_no' => 'FFFFF0001F',
                'address' => 'Shop 10, Eastern Express Highway, Mumbai',
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'company_id' => 1,
                'warehouse_id' => Warehouse::where('code', 'WH-MUM-001')->first()?->id,
                'location_id' => Location::where('name', 'Navi Mumbai Office')->first()?->id,
                'name' => 'Navi Mumbai Franchise',
                'code' => 'FR-NMU-001',
                'owner_name' => 'Sunita Patil',
                'phone' => '9876540002',
                'email' => 'sunita.franchise@trumac.com',
                'gst_no' => '27FFFFF0002F1Z5',
                'pan_no' => 'FFFFF0002F',
                'address' => 'Plot 5, Sector 11, Vashi, Navi Mumbai',
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'company_id' => 1,
                'warehouse_id' => Warehouse::where('code', 'WH-PUN-001')->first()?->id,
                'location_id' => Location::where('name', 'Pune Branch')->first()?->id,
                'name' => 'Pune Central Franchise',
                'code' => 'FR-PUN-C-001',
                'owner_name' => 'Ganesh Deshmukh',
                'phone' => '9876540003',
                'email' => 'ganesh.franchise@trumac.com',
                'gst_no' => '27FFFFF0003F1Z5',
                'pan_no' => 'FFFFF0003F',
                'address' => 'FC Road, Near Deccan Gymkhana, Pune',
                'is_active' => true,
                'is_deleted' => false,
            ],
        ];

        foreach ($franchises as $franchise) {
            Franchise::firstOrCreate(
                [
                    'code' => $franchise['code'],
                    'company_id' => $franchise['company_id'],
                ],
                $franchise
            );
        }
    }
}
