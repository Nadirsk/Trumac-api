<?php

namespace Database\Seeders;

use App\Models\RetailerRating;
use App\Models\Retailer;
use App\Models\User;
use Illuminate\Database\Seeder;

class RetailerRatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $retailers = Retailer::where('is_deleted', false)->get();
        $users = User::whereNotNull('position_id')->take(5)->get();

        if ($retailers->isEmpty() || $users->isEmpty()) {
            return;
        }

        $ratings = [];
        foreach ($retailers->take(3) as $retailer) {
            foreach ($users->take(2) as $user) {
                $ratings[] = [
                    'retailer_id' => $retailer->id,
                    'rated_by' => $user->id,
                    'rating' => rand(35, 50) / 10, // 3.5 to 5.0
                    'comment' => 'Good service and timely payments.',
                ];
            }
        }

        foreach ($ratings as $rating) {
            RetailerRating::firstOrCreate(
                [
                    'retailer_id' => $rating['retailer_id'],
                    'rated_by' => $rating['rated_by'],
                ],
                $rating
            );
        }
    }
}
