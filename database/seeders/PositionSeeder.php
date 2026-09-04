<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Position Hierarchy (mapped to Roles):
     *
     * Role ID 3: USER (External Users)
     *   - Retailer
     *
     * Role ID 4: IT ADMIN (Internal Team - Head Office)
     *   - IT Team Leader
     *   - IT Employee
     *   - IT Driver
     *   - IT Purchase
     *
     * Role ID 5: WAREHOUSE ADMIN (Warehouse Operations)
     *   - Warehouse Billing
     *   - Warehouse Driver
     *
     * Role ID 6: CG ADMIN (Company Godown Operations)
     *   - CG Billing
     *   - CG Driver
     *
     * Role ID 7: FRANCHISE ADMIN (Franchise Operations)
     *   - Franchise Billing
     *   - Franchise Driver
     */
    public function run(): void
    {
        $positions = [

            // Role ID 4: IT ADMIN - Internal Team at Head Office
            ['company_id' => 1, 'role_id' => 4, 'name' => 'IT Admin'],
            ['company_id' => 1, 'role_id' => 4, 'name' => 'IT Team Leader'],
            ['company_id' => 1, 'role_id' => 4, 'name' => 'IT Employee'],
            ['company_id' => 1, 'role_id' => 4, 'name' => 'IT Driver'],
            ['company_id' => 1, 'role_id' => 4, 'name' => 'IT Purchase'],

            // Role ID 5: WAREHOUSE ADMIN - Warehouse Operations
            ['company_id' => 1, 'role_id' => 5, 'name' => 'Warehouse Admin'],
            ['company_id' => 1, 'role_id' => 5, 'name' => 'Warehouse Billing'],
            ['company_id' => 1, 'role_id' => 5, 'name' => 'Warehouse Driver'],

            // Role ID 6: CG ADMIN - Company Godown Operations
            ['company_id' => 1, 'role_id' => 6, 'name' => 'CG Admin'],
            ['company_id' => 1, 'role_id' => 6, 'name' => 'CG Billing'],
            ['company_id' => 1, 'role_id' => 6, 'name' => 'CG Driver'],

            // Role ID 7: FRANCHISE ADMIN - Franchise Operations
            ['company_id' => 1, 'role_id' => 7, 'name' => 'Franchise Admin'],
            ['company_id' => 1, 'role_id' => 7, 'name' => 'Franchise Billing'],
            ['company_id' => 1, 'role_id' => 7, 'name' => 'Franchise Driver'],
            // Role ID 3: USER - External Users
            ['company_id' => 1, 'role_id' => 3, 'name' => 'Retailer'],
        ];

        foreach ($positions as $position) {
            Position::updateOrCreate(
                ['name' => $position['name'], 'company_id' => $position['company_id']],
                $position
            );
        }
    }
}
