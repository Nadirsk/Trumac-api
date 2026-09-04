<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $leaveTypes = [
            [
                'company_id' => 1,
                'name' => 'Casual Leave',
                'is_paid' => true,
                'max_days' => 12,
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'company_id' => 1,
                'name' => 'Sick Leave',
                'is_paid' => true,
                'max_days' => 10,
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'company_id' => 1,
                'name' => 'Earned Leave',
                'is_paid' => true,
                'max_days' => 15,
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'company_id' => 1,
                'name' => 'Maternity Leave',
                'is_paid' => true,
                'max_days' => 180,
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'company_id' => 1,
                'name' => 'Paternity Leave',
                'is_paid' => true,
                'max_days' => 7,
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'company_id' => 1,
                'name' => 'Loss of Pay',
                'is_paid' => false,
                'max_days' => 365,
                'is_active' => true,
                'is_deleted' => false,
            ],
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::firstOrCreate(
                [
                    'name' => $leaveType['name'],
                    'company_id' => $leaveType['company_id'],
                ],
                $leaveType
            );
        }
    }
}
