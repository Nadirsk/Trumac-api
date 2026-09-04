<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Role Hierarchy:
     * 1. SUPER ADMIN     - Web Panel (Desktop) - Full system control
     * 2. ADMIN           - Mobile App - Generic admin (reserved)
     * 3. USER            - Mobile App - External users (Retailer position)
     * 4. IT ADMIN        - Mobile App - Internal Team at Head Office
     * 5. WAREHOUSE ADMIN - Mobile App - Warehouse Operations
     * 6. CG ADMIN        - Mobile App - Company Godown Operations
     * 7. FRANCHISE ADMIN - Mobile App - Franchise Operations
     */
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'name' => 'SUPER ADMIN'],
            ['id' => 2, 'name' => 'ADMIN'],
            ['id' => 3, 'name' => 'USER'],
            ['id' => 4, 'name' => 'IT ADMIN'],
            ['id' => 5, 'name' => 'WAREHOUSE ADMIN'],
            ['id' => 6, 'name' => 'CG ADMIN'],
            ['id' => 7, 'name' => 'FRANCHISE ADMIN'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['id' => $role['id']],
                $role
            );
        }
    }
}
