<?php

namespace Database\Seeders;

use App\Models\RegularisationRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class RegularisationRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereNotNull('position_id')->take(3)->get();

        if ($users->isEmpty()) {
            return;
        }

        $requests = [];

        foreach ($users as $user) {
            $date = now()->subDays(rand(1, 7));

            // Pending request
            $requests[] = [
                'company_id' => 1,
                'user_id' => $user->id,
                'date' => $date->format('Y-m-d'),
                'reason' => 'Forgot to punch in/out - requesting attendance regularisation',
                'status' => 'pending',
                'is_deleted' => false,
            ];

            // Approved request
            $requests[] = [
                'company_id' => 1,
                'user_id' => $user->id,
                'date' => now()->subDays(rand(8, 15))->format('Y-m-d'),
                'reason' => 'Was on field duty, unable to punch attendance',
                'status' => 'approved',
                'approved_by' => User::where('email', 'ADMIN@GMAIL.COM')->first()?->id,
                'remarks' => 'Approved as user was on field duty',
                'is_deleted' => false,
            ];
        }

        foreach ($requests as $request) {
            RegularisationRequest::create($request);
        }
    }
}
