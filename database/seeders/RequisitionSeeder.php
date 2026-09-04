<?php

namespace Database\Seeders;

use App\Models\Requisition;
use App\Models\Warehouse;
use App\Models\Franchise;
use App\Models\CompanyGodown;
use App\Models\User;
use Illuminate\Database\Seeder;

class RequisitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouse = Warehouse::where('is_deleted', false)->first();
        $franchise = Franchise::where('is_deleted', false)->first();
        $companyGodown = CompanyGodown::where('is_deleted', false)->first();
        $user = User::where('email', 'nadirsk@gmail.com')->first();
        $cgBillingUser = User::where('email', 'anjali.cgbilling@gmail.com')->first();
        $cgAdminUser = User::where('email', 'cgadmin@gmail.com')->first();

        if (!$warehouse || !$user) {
            return;
        }

        $requisitions = [];
        $reqNumber = 1000;

        // Franchise requesting stock from warehouse
        $requisitions[] = [
            'company_id' => 1,
            'from_location_type' => 'warehouse',
            'from_location_id' => $warehouse->id,
            'to_location_type' => 'franchise',
            'to_location_id' => $franchise->id,
            'requisition_number' => 'REQ-' . str_pad($reqNumber++, 6, '0', STR_PAD_LEFT),
            'request_date' => now()->subDays(rand(5, 15)),
            'required_date' => now()->addDays(rand(5, 10)),
            'status' => 'approved',
            'notes' => 'Urgent stock requirement for franchise - high demand items',
            'requested_by' => $user->id,
            'approved_by' => User::where('email', 'ADMIN@GMAIL.COM')->first()?->id,
            'approved_at' => now()->subDays(rand(3, 10)),
            'is_deleted' => false,
        ];

        // Pending requisition - warehouse requesting from head office
        $requisitions[] = [
            'company_id' => 1,
            'from_location_type' => 'head_office',
            'from_location_id' => null,
            'to_location_type' => 'warehouse',
            'to_location_id' => $warehouse->id,
            'requisition_number' => 'REQ-' . str_pad($reqNumber++, 6, '0', STR_PAD_LEFT),
            'request_date' => now()->subDays(rand(1, 5)),
            'required_date' => now()->addDays(rand(3, 7)),
            'status' => 'pending',
            'notes' => 'Stock replenishment request - waiting for approval',
            'requested_by' => $user->id,
            'is_deleted' => false,
        ];

        // CG Requisitions (if company godown exists)
        if ($companyGodown && $cgBillingUser) {
            // Pending requisition - CG requesting from Warehouse
            $requisitions[] = [
                'company_id' => 1,
                'from_location_type' => 'warehouse',
                'from_location_id' => $warehouse->id,
                'to_location_type' => 'company_godown',
                'to_location_id' => $companyGodown->id,
                'requisition_number' => 'REQ-' . str_pad($reqNumber++, 6, '0', STR_PAD_LEFT),
                'request_date' => now()->subDays(rand(1, 3)),
                'required_date' => now()->addDays(rand(2, 5)),
                'status' => 'pending',
                'notes' => 'CG stock replenishment - urgent requirement for retail orders',
                'requested_by' => $cgBillingUser->id,
                'is_deleted' => false,
            ];

            // Approved requisition - CG to Warehouse
            $requisitions[] = [
                'company_id' => 1,
                'from_location_type' => 'warehouse',
                'from_location_id' => $warehouse->id,
                'to_location_type' => 'company_godown',
                'to_location_id' => $companyGodown->id,
                'requisition_number' => 'REQ-' . str_pad($reqNumber++, 6, '0', STR_PAD_LEFT),
                'request_date' => now()->subDays(rand(5, 10)),
                'required_date' => now()->addDays(rand(1, 3)),
                'status' => 'approved',
                'notes' => 'Approved CG requisition - ready for fulfillment',
                'requested_by' => $cgBillingUser->id,
                'approved_by' => $cgAdminUser?->id,
                'approved_at' => now()->subDays(rand(2, 5)),
                'is_deleted' => false,
            ];

            // Cancelled requisition - CG to Warehouse
            $requisitions[] = [
                'company_id' => 1,
                'from_location_type' => 'warehouse',
                'from_location_id' => $warehouse->id,
                'to_location_type' => 'company_godown',
                'to_location_id' => $companyGodown->id,
                'requisition_number' => 'REQ-' . str_pad($reqNumber++, 6, '0', STR_PAD_LEFT),
                'request_date' => now()->subDays(rand(7, 12)),
                'required_date' => now()->addDays(rand(1, 3)),
                'status' => 'cancelled',
                'notes' => 'Cancelled due to insufficient warehouse stock',
                'requested_by' => $cgBillingUser->id,
                'is_deleted' => false,
            ];
        }

        foreach ($requisitions as $requisition) {
            Requisition::firstOrCreate(
                [
                    'requisition_number' => $requisition['requisition_number'],
                ],
                $requisition
            );
        }
    }
}
