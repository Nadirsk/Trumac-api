<?php

namespace Database\Seeders;

use App\Models\PjpRetailer;
use App\Models\Pjp;
use App\Models\Retailer;
use Illuminate\Database\Seeder;

class PjpRetailerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pjps = Pjp::where('is_deleted', false)->get();
        $retailers = Retailer::where('is_deleted', false)->get();

        if ($pjps->isEmpty() || $retailers->isEmpty()) {
            return;
        }

        $pjpRetailers = [];
        $visitTimes = ['09:00:00', '10:30:00', '12:00:00', '14:00:00', '16:00:00'];

        foreach ($pjps as $pjp) {
            // Assign 3-5 retailers to each PJP
            $numberOfRetailers = min(rand(3, 5), $retailers->count());
            $selectedRetailers = $retailers->random($numberOfRetailers);

            foreach ($selectedRetailers as $index => $retailer) {
                $pjpRetailers[] = [
                    'pjp_id' => $pjp->id,
                    'retailer_id' => $retailer->id,
                    'sequence' => $index + 1,
                    'planned_visit_time' => $visitTimes[$index % count($visitTimes)],
                    'expected_duration' => rand(15, 60), // 15-60 minutes
                    'is_active' => true,
                ];
            }
        }

        foreach ($pjpRetailers as $pjpRetailer) {
            PjpRetailer::firstOrCreate(
                [
                    'pjp_id' => $pjpRetailer['pjp_id'],
                    'retailer_id' => $pjpRetailer['retailer_id'],
                ],
                $pjpRetailer
            );
        }
    }
}
