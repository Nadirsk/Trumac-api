<?php

namespace Database\Seeders;

use App\Models\Pjp;
use App\Models\User;
use App\Models\Franchise;
use Illuminate\Database\Seeder;

class PjpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = User::where('position_id',3)->take(3)->get();
        $franchises = Franchise::where('is_deleted', false)->get();

        if ($employees->isEmpty()) {
            return;
        }

        $pjps = [];
        $days = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];

        foreach ($employees as $employee) {
            // Create PJP for each weekday (Monday to Friday)
            for ($day = 1; $day <= 5; $day++) {
                $franchise = $franchises->isNotEmpty() ? $franchises->random() : null;

                $pjps[] = [
                    'company_id' => 1,
                    'employee_id' => $employee->id,
                    'franchise_id' => $franchise?->id,
                    'day_of_week' => $day,
                    'name' => $days[$day] . ' Route - ' . ($employee->user_name ?? 'Employee'),
                    'notes' => 'Weekly visit schedule for ' . $days[$day],
                    'is_active' => true,
                    'is_deleted' => false,
                ];
            }
        }

        foreach ($pjps as $pjp) {
            Pjp::firstOrCreate(
                [
                    'employee_id' => $pjp['employee_id'],
                    'day_of_week' => $pjp['day_of_week'],
                ],
                $pjp
            );
        }
    }
}
