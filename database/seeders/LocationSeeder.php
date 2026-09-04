<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'company_id' => 1,
                'name' => 'Mumbai Central',
                'address' => 'Shop No. 12, Central Avenue',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400008',
                'latitude' => 19.0176,
                'longitude' => 72.8561,
                'type' => 'warehouse',
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'company_id' => 1,
                'name' => 'Pune Branch',
                'address' => 'Plot No. 45, Industrial Area',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'pincode' => '411014',
                'latitude' => 18.5204,
                'longitude' => 73.8567,
                'type' => 'warehouse',
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'company_id' => 1,
                'name' => 'Navi Mumbai Office',
                'address' => 'Sector 2, Kharghar',
                'city' => 'Navi Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '410210',
                'latitude' => 19.0437,
                'longitude' => 73.0669,
                'type' => 'headquarters',
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'company_id' => 1,
                'name' => 'Thane Warehouse',
                'address' => 'Ghodbunder Road',
                'city' => 'Thane',
                'state' => 'Maharashtra',
                'pincode' => '400607',
                'latitude' => 19.2183,
                'longitude' => 72.9781,
                'type' => 'warehouse',
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'company_id' => 1,
                'name' => 'Nashik Distribution Center',
                'address' => 'MIDC Area, Satpur',
                'city' => 'Nashik',
                'state' => 'Maharashtra',
                'pincode' => '422007',
                'latitude' => 20.0080,
                'longitude' => 73.7682,
                'type' => 'other',
                'is_active' => true,
                'is_deleted' => false,
            ],
        ];

        foreach ($locations as $location) {
            Location::firstOrCreate(
                [
                    'name' => $location['name'],
                    'company_id' => $location['company_id'],
                ],
                $location
            );
        }
    }
}
