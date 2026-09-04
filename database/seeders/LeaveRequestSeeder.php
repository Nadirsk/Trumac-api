<?php

namespace Database\Seeders;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeaveRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereNotNull('position_id')->take(5)->get();
        $leaveTypes = LeaveType::where('is_deleted', false)->get();

        if ($users->isEmpty() || $leaveTypes->isEmpty()) {
            return;
        }

        $leaveRequests = [];

        foreach ($users as $user) {
            $leaveType = $leaveTypes->random();

            // Approved leave
            $leaveRequests[] = [
                'company_id' => 1,
                'user_id' => $user->id,
                'leave_type_id' => $leaveType->id,
                'from_date' => now()->subDays(10),
                'to_date' => now()->subDays(9),
                'reason' => 'Personal work',
                'status' => 'approved',
                'approved_by' => User::where('email', 'ADMIN@GMAIL.COM')->first()?->id,
                'remarks' => 'Approved for personal work',
                'is_deleted' => false,
            ];

            // Pending leave
            $leaveRequests[] = [
                'company_id' => 1,
                'user_id' => $user->id,
                'leave_type_id' => $leaveTypes->random()->id,
                'from_date' => now()->addDays(5),
                'to_date' => now()->addDays(6),
                'reason' => 'Medical appointment',
                'status' => 'pending',
                'is_deleted' => false,
            ];
        }

        foreach ($leaveRequests as $leaveRequest) {
            LeaveRequest::create($leaveRequest);
        }
    }
}
