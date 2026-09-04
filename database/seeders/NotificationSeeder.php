<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
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

        $notifications = [];

        foreach ($users as $user) {
            $notifications[] = [
                'company_id' => 1,
                'user_id' => $user->id,
                'title' => 'Welcome to Trumac',
                'body' => 'Welcome to the Trumac mobile application. Start managing your tasks efficiently.',
                'type' => 'welcome',
                'is_read' => false,
                'data' => json_encode(['action' => 'onboarding']),
            ];

            $notifications[] = [
                'company_id' => 1,
                'user_id' => $user->id,
                'title' => 'Leave Request Approved',
                'body' => 'Your leave request has been approved by your manager.',
                'type' => 'leave_request',
                'is_read' => true,
                'data' => json_encode(['leave_id' => rand(1, 100), 'status' => 'approved']),
            ];

            $notifications[] = [
                'company_id' => 1,
                'user_id' => $user->id,
                'title' => 'New Order Assigned',
                'body' => 'You have been assigned a new order for delivery. Check order details.',
                'type' => 'order',
                'is_read' => false,
                'data' => json_encode(['order_id' => rand(1000, 9999), 'priority' => 'high']),
            ];

            $notifications[] = [
                'company_id' => 1,
                'user_id' => $user->id,
                'title' => 'Attendance Reminder',
                'body' => 'Don\'t forget to punch in your attendance today.',
                'type' => 'attendance',
                'is_read' => false,
                'data' => json_encode(['reminder_type' => 'punch_in']),
            ];
        }

        foreach ($notifications as $notification) {
            Notification::create($notification);
        }
    }
}
