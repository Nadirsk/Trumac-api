<?php

namespace Database\Seeders;

use App\Models\QuestionResponse;
use App\Models\QuestionnaireResponse;
use App\Models\Question;
use Illuminate\Database\Seeder;

class QuestionResponseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questionnaireResponses = QuestionnaireResponse::all();

        if ($questionnaireResponses->isEmpty()) {
            return;
        }

        $responses = [];

        foreach ($questionnaireResponses as $qResponse) {
            $questions = Question::where('questionnaire_id', $qResponse->questionnaire_id)
                ->where('is_deleted', false)
                ->get();

            foreach ($questions as $question) {
                $answer = '';
                $score = 0;

                switch ($question->type) {
                    case 'rating':
                        $answer = (string) rand(3, 5);
                        $score = (int) $answer * $question->weight;
                        break;
                    case 'single_choice':
                    case 'multiple_choice':
                        $options = is_array($question->options) ? $question->options : json_decode($question->options);
                        $answer = $options ? $options[array_rand($options)] : '';
                        $score = $question->weight;
                        break;
                    case 'yes_no':
                        $answer = rand(0, 1) ? 'Yes' : 'No';
                        $score = $answer === 'Yes' ? $question->weight : 0;
                        break;
                    case 'text':
                        $answer = 'Sample response for ' . substr($question->question_text, 0, 20);
                        $score = $question->weight;
                        break;
                }

                $responses[] = [
                    'questionnaire_response_id' => $qResponse->id,
                    'question_id' => $question->id,
                    'answer' => $answer,
                    'score' => $score,
                ];
            }
        }

        foreach ($responses as $response) {
            QuestionResponse::create($response);
        }
    }
}
