<?php

namespace Database\Seeders;

use App\Models\QuestionnaireResponse;
use App\Models\Questionnaire;
use App\Models\User;
use App\Models\Retailer;
use Illuminate\Database\Seeder;

class QuestionnaireResponseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questionnaires = Questionnaire::where('is_deleted', false)->get();
        $users = User::whereNotNull('position_id')->take(3)->get();
        $retailers = Retailer::where('is_deleted', false)->take(3)->get();

        if ($questionnaires->isEmpty() || ($users->isEmpty() && $retailers->isEmpty())) {
            return;
        }

        $responses = [];

        foreach ($questionnaires as $questionnaire) {
            // User responses for internal questionnaires
            if ($questionnaire->type === 'Store Visit') {
                foreach ($users as $user) {
                    $responses[] = [
                        'company_id' => $questionnaire->company_id,
                        'questionnaire_id' => $questionnaire->id,
                        'respondent_type' => 'User',
                        'respondent_id' => $user->id,
                        'submitted_at' => now()->subDays(rand(1, 10)),
                        'is_active' => true,
                        'is_deleted' => false,
                    ];
                }
            }

            // Retailer responses
            if ($questionnaire->type === 'Retailer Feedback' || $questionnaire->type === 'Quality Assessment') {
                foreach ($retailers as $retailer) {
                    $responses[] = [
                        'company_id' => $questionnaire->company_id,
                        'questionnaire_id' => $questionnaire->id,
                        'respondent_type' => 'Retailer',
                        'respondent_id' => $retailer->id,
                        'submitted_at' => now()->subDays(rand(1, 15)),
                        'is_active' => true,
                        'is_deleted' => false,
                    ];
                }
            }
        }

        foreach ($responses as $response) {
            QuestionnaireResponse::create($response);
        }
    }
}
