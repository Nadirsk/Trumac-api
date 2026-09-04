<?php

namespace Database\Seeders;

use App\Models\Grn;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;
use App\Models\CompanyGodown;
use App\Models\Requisition;
use App\Models\User;
use Illuminate\Database\Seeder;

class GrnSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $purchaseOrders = PurchaseOrder::where('status', 'approved')->where('is_deleted', false)->get();
        $warehouse = Warehouse::where('is_deleted', false)->first();
        $user = User::where('email', 'nadirsk@gmail.com')->first();

        if ($purchaseOrders->isEmpty() || !$warehouse || !$user) {
            return;
        }

        $grns = [];
        $grnNumber = 1000;

        foreach ($purchaseOrders->take(3) as $po) {
            $grns[] = [
                'company_id' => $po->company_id,
                'reference_type' => 'purchase_order',
                'reference_id' => $po->id,
                'location_type' => 'warehouse',
                'location_id' => $warehouse->id,
                'grn_number' => 'GRN-' . str_pad($grnNumber++, 6, '0', STR_PAD_LEFT),
                'received_date' => now()->subDays(rand(1, 10)),
                'received_by' => $user->id,
                'status' => 'completed',
                'notes' => 'All items received in good condition. Vendor invoice verified.',
                'vehicle_number' => 'MH-' . rand(10, 20) . '-' . strtoupper(substr(md5(rand()), 0, 4)),
                'driver_name' => ['Rajesh Kumar', 'Sunil Patil', 'Vijay Sharma', 'Amit Singh'][rand(0, 3)],
                'is_deleted' => false,
            ];
        }

        foreach ($grns as $grn) {
            Grn::firstOrCreate(
                [
                    'grn_number' => $grn['grn_number'],
                ],
                $grn
            );
        }

        // Create CG-specific GRN records
        $this->seedCGGrns($grnNumber);
    }

    /**
     * Seed GRN records for Company Godown (CG)
     * CG receives goods from warehouse via requisitions
     */
    private function seedCGGrns(int &$grnNumber): void
    {
        $companyGodown = CompanyGodown::where('is_deleted', false)->first();
        $cgBillingUser = User::where('email', 'anjali.cgbilling@gmail.com')->first();
        $cgDriverUser = User::where('email', 'sunil.cgdriver@gmail.com')->first();

        // Get approved requisitions for CG
        $cgRequisitions = Requisition::where('to_location_type', 'company_godown')
            ->where('to_location_id', 1)
            ->where('status', 'approved')
            ->where('is_deleted', false)
            ->get();

        if (!$companyGodown || !$cgBillingUser || $cgRequisitions->isEmpty()) {
            return;
        }

        $cgGrns = [];

        foreach ($cgRequisitions->take(3) as $requisition) {
            // Completed GRN - goods received from warehouse
            $cgGrns[] = [
                'company_id' => 1,
                'reference_type' => 'requisition',
                'reference_id' => $requisition->id,
                'location_type' => 'company_godown',
                'location_id' => $companyGodown->id,
                'grn_number' => 'GRN-CG-' . str_pad($grnNumber++, 6, '0', STR_PAD_LEFT),
                'received_date' => now()->subDays(rand(1, 7)),
                'received_by' => $cgBillingUser->id,
                'status' => 'completed',
                'notes' => 'Stock received from warehouse against requisition. All items verified and matched.',
                'vehicle_number' => 'MH-' . rand(10, 20) . '-CG-' . strtoupper(substr(md5(rand()), 0, 3)),
                'driver_name' => $cgDriverUser?->first_name . ' ' . $cgDriverUser?->last_name ?? 'Warehouse Driver',
                'is_deleted' => false,
            ];

            // Draft GRN - goods in transit (not yet received)
            $cgGrns[] = [
                'company_id' => 1,
                'reference_type' => 'requisition',
                'reference_id' => $requisition->id,
                'location_type' => 'company_godown',
                'location_id' => $companyGodown->id,
                'grn_number' => 'GRN-CG-' . str_pad($grnNumber++, 6, '0', STR_PAD_LEFT),
                'received_date' => now(),
                'received_by' => $cgBillingUser->id,
                'status' => 'draft',
                'notes' => 'Stock in transit from warehouse - expected delivery today',
                'vehicle_number' => 'MH-' . rand(10, 20) . '-CG-' . strtoupper(substr(md5(rand()), 0, 3)),
                'driver_name' => 'Ravi Kumar',
                'is_deleted' => false,
            ];
        }

        // Add one cancelled GRN (order cancelled)
        if ($cgRequisitions->isNotEmpty()) {
            $cgGrns[] = [
                'company_id' => 1,
                'reference_type' => 'requisition',
                'reference_id' => $cgRequisitions->first()->id,
                'location_type' => 'company_godown',
                'location_id' => $companyGodown->id,
                'grn_number' => 'GRN-CG-' . str_pad($grnNumber++, 6, '0', STR_PAD_LEFT),
                'received_date' => now()->subDays(rand(5, 10)),
                'received_by' => $cgBillingUser->id,
                'status' => 'cancelled',
                'notes' => 'GRN cancelled - incorrect items dispatched from warehouse',
                'vehicle_number' => 'MH-' . rand(10, 20) . '-CG-' . strtoupper(substr(md5(rand()), 0, 3)),
                'driver_name' => 'Lakshmi Devi',
                'is_deleted' => false,
            ];
        }

        foreach ($cgGrns as $grn) {
            Grn::firstOrCreate(
                [
                    'grn_number' => $grn['grn_number'],
                ],
                $grn
            );
        }
    }
}
