<?php

namespace Database\Seeders;

use App\Models\Questionnaire;
use Illuminate\Database\Seeder;

class QuestionnaireSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questionnaires = [
            [
                'company_id' => 1,
                'name' => 'Retailer Feedback Survey',
                'description' => 'Monthly feedback survey for retailers to improve our services',
                'is_active' => true,
                'is_deleted' => false,
                'created_at' => now()->subDays(30),
            ],
            [
                'company_id' => 1,
                'name' => 'Product Quality Assessment',
                'description' => 'Quarterly product quality assessment questionnaire',
                'is_active' => true,
                'is_deleted' => false,
                'created_at' => now()->subDays(15),
            ],
            [
                'company_id' => 1,
                'name' => 'Store Visit Checklist',
                'description' => 'Daily store visit checklist for field executives',
                'is_active' => true,
                'is_deleted' => false,
                'created_at' => now()->subDays(7),
            ],
        ];

        foreach ($questionnaires as $questionnaire) {
            Questionnaire::firstOrCreate(
                [
                    'name' => $questionnaire['name'],
                    'company_id' => $questionnaire['company_id'],
                ],
                $questionnaire
            );
        }
    }
}
