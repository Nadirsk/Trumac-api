<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Questionnaire;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questionnaires = Questionnaire::where('is_deleted', false)->get();

        if ($questionnaires->isEmpty()) {
            return;
        }

        $questions = [];

        foreach ($questionnaires as $questionnaire) {
            if ($questionnaire->name === 'Retailer Feedback Survey') {
                $questions[] = [
                    'questionnaire_id' => $questionnaire->id,
                    'question_text' => 'How satisfied are you with our product quality?',
                    'type' => 'rating',
                    'options' => ['1', '2', '3', '4', '5'],
                    'is_required' => true,
                    'sequence' => 1,
                    'is_active' => true,
                    'is_deleted' => false,
                ];

                $questions[] = [
                    'questionnaire_id' => $questionnaire->id,
                    'question_text' => 'How would you rate our delivery service?',
                    'type' => 'rating',
                    'options' => ['1', '2', '3', '4', '5'],
                    'is_required' => true,
                    'sequence' => 2,
                    'is_active' => true,
                    'is_deleted' => false,
                ];

                $questions[] = [
                    'questionnaire_id' => $questionnaire->id,
                    'question_text' => 'Any suggestions for improvement?',
                    'type' => 'text',
                    'is_required' => false,
                    'sequence' => 3,
                    'is_active' => true,
                    'is_deleted' => false,
                ];
            } elseif ($questionnaire->name === 'Product Quality Assessment') {
                $questions[] = [
                    'questionnaire_id' => $questionnaire->id,
                    'question_text' => 'Were products delivered in good condition?',
                    'type' => 'multiple_choice',
                    'options' => ['Yes', 'No', 'Partially'],
                    'is_required' => true,
                    'sequence' => 1,
                    'is_active' => true,
                    'is_deleted' => false,
                ];

                $questions[] = [
                    'questionnaire_id' => $questionnaire->id,
                    'question_text' => 'Rate product packaging quality',
                    'type' => 'rating',
                    'options' => ['1', '2', '3', '4', '5'],
                    'is_required' => true,
                    'sequence' => 2,
                    'is_active' => true,
                    'is_deleted' => false,
                ];
            } elseif ($questionnaire->name === 'Store Visit Checklist') {
                $questions[] = [
                    'questionnaire_id' => $questionnaire->id,
                    'question_text' => 'Is the store clean and organized?',
                    'type' => 'multiple_choice',
                    'options' => ['Yes', 'No'],
                    'is_required' => true,
                    'sequence' => 1,
                    'is_active' => true,
                    'is_deleted' => false,
                ];

                $questions[] = [
                    'questionnaire_id' => $questionnaire->id,
                    'question_text' => 'Stock availability status',
                    'type' => 'multiple_choice',
                    'options' => ['Adequate', 'Low', 'Out of Stock'],
                    'is_required' => true,
                    'sequence' => 2,
                    'is_active' => true,
                    'is_deleted' => false,
                ];

                $questions[] = [
                    'questionnaire_id' => $questionnaire->id,
                    'question_text' => 'Additional observations',
                    'type' => 'text',
                    'is_required' => false,
                    'sequence' => 3,
                    'is_active' => true,
                    'is_deleted' => false,
                ];
            }
        }

        foreach ($questions as $question) {
            Question::create($question);
        }
    }
}
