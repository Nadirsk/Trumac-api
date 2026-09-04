<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereNotNull('position_id')->take(5)->get();

        if ($users->isEmpty()) {
            return;
        }

        $attendances = [];

        // Create attendance for last 10 days
        foreach ($users as $user) {
            for ($i = 1; $i <= 10; $i++) {
                $date = now()->subDays($i);
                $punchInTime = $date->copy()->setTime(9, rand(0, 30), 0);
                $punchOutTime = $date->copy()->setTime(18, rand(0, 30), 0);

                $attendances[] = [
                    'company_id' => 1,
                    'user_id' => $user->id,
                    'date' => $date->format('Y-m-d'),
                    'punch_in_time' => $punchInTime,
                    'punch_out_time' => $punchOutTime,
                    'punch_in_latitude' => 19.0760 + (rand(-100, 100) / 10000),
                    'punch_in_longitude' => 72.8777 + (rand(-100, 100) / 10000),
                    'punch_out_latitude' => 19.0760 + (rand(-100, 100) / 10000),
                    'punch_out_longitude' => 72.8777 + (rand(-100, 100) / 10000),
                    'status' => 'present',
                    'is_deleted' => false,
                ];
            }
        }

        foreach ($attendances as $attendance) {
            Attendance::firstOrCreate(
                [
                    'user_id' => $attendance['user_id'],
                    'date' => $attendance['date'],
                ],
                $attendance
            );
        }
    }
}
