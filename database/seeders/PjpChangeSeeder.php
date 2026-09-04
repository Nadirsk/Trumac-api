<?php

namespace Database\Seeders;

use App\Models\PjpChange;
use App\Models\Pjp;
use App\Models\Retailer;
use App\Models\User;
use Illuminate\Database\Seeder;

class PjpChangeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pjps = Pjp::where('is_deleted', false)->take(3)->get();
        $retailers = Retailer::where('is_deleted', false)->get();
        $admin = User::where('email', 'ADMIN@GMAIL.COM')->first();

        if ($pjps->isEmpty() || $retailers->isEmpty()) {
            return;
        }

        $changes = [];
        $actions = ['add', 'remove', 'swap', 'reorder'];

        foreach ($pjps as $pjp) {
            // Approved change request
            $changes[] = [
                'company_id' => $pjp->company_id,
                'pjp_id' => $pjp->id,
                'date' => now()->addDays(rand(1, 7)),
                'action' => 'add',
                'retailer_id' => $retailers->random()->id,
                'reason' => 'Need to add new retailer to the route',
                'status' => 'approved',
                'requested_by' => $pjp->employee_id,
                'approved_by' => $admin?->id,
                'remarks' => 'Approved - valid business need',
                'approved_at' => now()->subDays(rand(1, 3)),
                'is_deleted' => false,
            ];

            // Pending change request
            $changes[] = [
                'company_id' => $pjp->company_id,
                'pjp_id' => $pjp->id,
                'date' => now()->addDays(rand(8, 14)),
                'action' => 'swap',
                'retailer_id' => $retailers->random()->id,
                'swap_retailer_id' => $retailers->random()->id,
                'reason' => 'Need to swap retailer order for better route efficiency',
                'status' => 'pending',
                'requested_by' => $pjp->employee_id,
                'is_deleted' => false,
            ];
        }

        foreach ($changes as $change) {
            PjpChange::create($change);
        }
    }
}
