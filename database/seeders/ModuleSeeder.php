<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $moduleArray = [
            // Core Modules
            ['company_id' => 1, 'name' => 'COMPANIES'],
            ['company_id' => 1, 'name' => 'USERS'],
            ['company_id' => 1, 'name' => 'POSITIONS'],
            ['company_id' => 1, 'name' => 'PERMISSIONS'],
            ['company_id' => 1, 'name' => 'MODULES'],
            ['company_id' => 1, 'name' => 'ROLES'],

            // Master Data Modules
            ['company_id' => 1, 'name' => 'SKU_CATEGORIES'],
            ['company_id' => 1, 'name' => 'SKUS'],
            ['company_id' => 1, 'name' => 'LOCATIONS'],
            ['company_id' => 1, 'name' => 'VALUES'],

            // Distribution Network Modules
            ['company_id' => 1, 'name' => 'WAREHOUSES'],
            ['company_id' => 1, 'name' => 'COMPANY_GODOWNS'],
            ['company_id' => 1, 'name' => 'FRANCHISES'],
            ['company_id' => 1, 'name' => 'RETAILERS'],
            ['company_id' => 1, 'name' => 'VENDORS'],

            // Operations Modules
            ['company_id' => 1, 'name' => 'SALES_ORDERS'],
            ['company_id' => 1, 'name' => 'PURCHASE_ORDERS'],
            ['company_id' => 1, 'name' => 'GRNS'],
            ['company_id' => 1, 'name' => 'REQUISITIONS'],
            ['company_id' => 1, 'name' => 'INVENTORIES'],

            // Sales & Field Force Modules
            ['company_id' => 1, 'name' => 'PJPS'],
            ['company_id' => 1, 'name' => 'PJP_CHANGES'],
            ['company_id' => 1, 'name' => 'CASH_COLLECTIONS'],
            ['company_id' => 1, 'name' => 'JOURNEY_PLANS'],

            // HR & Attendance Modules
            ['company_id' => 1, 'name' => 'ATTENDANCES'],
            ['company_id' => 1, 'name' => 'LEAVE_TYPES'],
            ['company_id' => 1, 'name' => 'LEAVE_REQUESTS'],

            // Driver & Visits
            ['company_id' => 1, 'name' => 'RETAILER_VISITS'],
            ['company_id' => 1, 'name' => 'DRIVER_DOCUMENTS'],

            // Quality & Surveys
            ['company_id' => 1, 'name' => 'QUESTIONNAIRES'],
            ['company_id' => 1, 'name' => 'CHALLANS'],

            // Approvals
            ['company_id' => 1, 'name' => 'APPROVALS'],

            // System Modules
            ['company_id' => 1, 'name' => 'NOTIFICATIONS'],
            ['company_id' => 1, 'name' => 'REPORTS'],
            ['company_id' => 1, 'name' => 'DASHBOARD'],
        ];

        foreach ($moduleArray as $module) {
            Module::firstOrCreate(['name' => $module['name']], $module);
        }
    }
}
