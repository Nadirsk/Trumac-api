<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionnaireResponse;
use App\Models\QuestionResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuestionnairesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'update', 'clear', 'restore', 'getResponses']);
    }

    /**
     * @OA\Get(
     *     path="/api/questionnaires",
     *     tags={"Questionnaires"},
     *     summary="Get all questionnaires",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = Questionnaire::query()->with('questions');
            $query = Utility::prepareSearchQuery($query, $request, new Questionnaire());
            $query->orderBy('id', 'desc');
            $questionnaires = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title' => 'Questionnaires',
                'sub-title' => 'Questionnaires fetched successfully',
                'success' => true,
                'data' => $questionnaires,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/questionnaires",
     *     tags={"Questionnaires"},
     *     summary="Create a new questionnaire",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'questions' => 'array',
            'questions.*.question_text' => 'required|string',
            'questions.*.type' => 'required|string|in:text,single_choice,multiple_choice,rating,yes_no',
            'questions.*.options' => 'nullable|array',
            'questions.*.weight' => 'integer|min:1',
            'questions.*.sequence' => 'integer|min:0',
            'questions.*.is_required' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $questionnaire = Questionnaire::create([
                'name' => $attributes['name'],
                'description' => $attributes['description'] ?? null,
                'is_active' => $attributes['is_active'] ?? true,
                'company_id' => $request->company->id,
            ]);

            // Create questions if provided
            if (!empty($attributes['questions'])) {
                foreach ($attributes['questions'] as $index => $questionData) {
                    $questionnaire->questions()->create([
                        'question_text' => $questionData['question_text'],
                        'type' => $questionData['type'],
                        'options' => $questionData['options'] ?? null,
                        'weight' => $questionData['weight'] ?? 1,
                        'sequence' => $questionData['sequence'] ?? $index,
                        'is_required' => $questionData['is_required'] ?? true,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'title' => 'Questionnaire',
                'sub-title' => 'Questionnaire created successfully',
                'success' => true,
                'data' => $questionnaire->load('questions'),
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/questionnaires/{id}",
     *     tags={"Questionnaires"},
     *     summary="Get single questionnaire",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Request $request)
    {
        $questionnaire = Questionnaire::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->with(['questions' => function ($q) {
                $q->where('is_deleted', false)->orderBy('sequence');
            }])
            ->first();

        if (!$questionnaire) {
            return response()->json([
                'title' => 'Questionnaire',
                'sub-title' => 'Questionnaire not found',
                'success' => false,
            ], 404);
        }

        return response()->json([
            'title' => 'Questionnaire',
            'sub-title' => 'Questionnaire fetched successfully',
            'success' => true,
            'data' => $questionnaire,
        ], 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/questionnaires/{id}",
     *     tags={"Questionnaires"},
     *     summary="Update a questionnaire",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(Request $request)
    {
        $questionnaire = Questionnaire::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$questionnaire) {
            return response()->json([
                'title' => 'Questionnaire',
                'sub-title' => 'Questionnaire not found',
                'success' => false,
            ], 404);
        }

        $attributes = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'questions' => 'array',
            'questions.*.id' => 'nullable|integer',
            'questions.*.question_text' => 'required|string',
            'questions.*.type' => 'required|string|in:text,single_choice,multiple_choice,rating,yes_no',
            'questions.*.options' => 'nullable|array',
            'questions.*.weight' => 'integer|min:1',
            'questions.*.sequence' => 'integer|min:0',
            'questions.*.is_required' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $questionnaire->update([
                'name' => $attributes['name'],
                'description' => $attributes['description'] ?? null,
                'is_active' => $attributes['is_active'] ?? true,
            ]);

            // Update questions if provided
            if (isset($attributes['questions'])) {
                $existingIds = $questionnaire->questions()->pluck('id')->toArray();
                $incomingIds = [];

                foreach ($attributes['questions'] as $index => $questionData) {
                    if (!empty($questionData['id'])) {
                        // Update existing question
                        $question = Question::find($questionData['id']);
                        if ($question && $question->questionnaire_id === $questionnaire->id) {
                            $question->update([
                                'question_text' => $questionData['question_text'],
                                'type' => $questionData['type'],
                                'options' => $questionData['options'] ?? null,
                                'weight' => $questionData['weight'] ?? 1,
                                'sequence' => $questionData['sequence'] ?? $index,
                                'is_required' => $questionData['is_required'] ?? true,
                            ]);
                            $incomingIds[] = $question->id;
                        }
                    } else {
                        // Create new question
                        $question = $questionnaire->questions()->create([
                            'question_text' => $questionData['question_text'],
                            'type' => $questionData['type'],
                            'options' => $questionData['options'] ?? null,
                            'weight' => $questionData['weight'] ?? 1,
                            'sequence' => $questionData['sequence'] ?? $index,
                            'is_required' => $questionData['is_required'] ?? true,
                        ]);
                        $incomingIds[] = $question->id;
                    }
                }

                // Soft delete removed questions
                $idsToDelete = array_diff($existingIds, $incomingIds);
                if ($idsToDelete) {
                    Question::whereIn('id', $idsToDelete)->update(['is_deleted' => true]);
                }
            }

            DB::commit();

            return response()->json([
                'title' => 'Questionnaire',
                'sub-title' => 'Questionnaire updated successfully',
                'success' => true,
                'data' => $questionnaire->load('questions'),
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/questionnaires/delete/{id}",
     *     tags={"Questionnaires"},
     *     summary="Soft delete a questionnaire",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function clear(Request $request)
    {
        $questionnaire = Questionnaire::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$questionnaire) {
            return response()->json([
                'title' => 'Questionnaire',
                'sub-title' => 'Questionnaire not found',
                'success' => false,
            ], 404);
        }

        $questionnaire->update(['is_deleted' => true]);

        return response()->json([
            'title' => 'Questionnaire',
            'sub-title' => 'Questionnaire deleted successfully',
            'success' => true,
            'data' => $questionnaire,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/questionnaires/restore/{id}",
     *     tags={"Questionnaires"},
     *     summary="Restore a soft-deleted questionnaire",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function restore(Request $request)
    {
        $questionnaire = Questionnaire::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$questionnaire) {
            return response()->json([
                'title' => 'Questionnaire',
                'sub-title' => 'Questionnaire not found',
                'success' => false,
            ], 404);
        }

        $questionnaire->update(['is_deleted' => false]);

        return response()->json([
            'title' => 'Questionnaire',
            'sub-title' => 'Questionnaire restored successfully',
            'success' => true,
            'data' => $questionnaire,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/questionnaires/{id}/submit",
     *     tags={"Questionnaires"},
     *     summary="Submit questionnaire response for a retailer",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function submitResponse(Request $request)
    {
        $questionnaire = Questionnaire::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->where('is_active', true)
            ->where('is_deleted', false)
            ->first();

        if (!$questionnaire) {
            return response()->json([
                'title' => 'Questionnaire',
                'sub-title' => 'Active questionnaire not found',
                'success' => false,
            ], 404);
        }

        $attributes = $request->validate([
            'retailer_id' => 'required|integer|exists:retailers,id',
            'remarks' => 'nullable|string',
            'responses' => 'required|array',
            'responses.*.question_id' => 'required|integer|exists:questions,id',
            'responses.*.answer' => 'nullable',
        ]);

        DB::beginTransaction();
        try {
            // Create questionnaire response
            $questionnaireResponse = QuestionnaireResponse::create([
                'questionnaire_id' => $questionnaire->id,
                'retailer_id' => $attributes['retailer_id'],
                'responded_by' => $request->user()->id,
                'remarks' => $attributes['remarks'] ?? null,
                'company_id' => $request->company->id,
            ]);

            // Create individual question responses
            foreach ($attributes['responses'] as $responseData) {
                $question = Question::find($responseData['question_id']);
                if ($question && $question->questionnaire_id === $questionnaire->id) {
                    $score = $question->calculateScore($responseData['answer']);

                    QuestionResponse::create([
                        'questionnaire_response_id' => $questionnaireResponse->id,
                        'question_id' => $responseData['question_id'],
                        'answer' => is_array($responseData['answer'])
                            ? json_encode($responseData['answer'])
                            : $responseData['answer'],
                        'score' => $score,
                    ]);
                }
            }

            // Calculate and update total scores
            $questionnaireResponse->calculateScores();

            DB::commit();

            return response()->json([
                'title' => 'Questionnaire Response',
                'sub-title' => 'Response submitted successfully',
                'success' => true,
                'data' => $questionnaireResponse->load(['questionResponses.question', 'retailer']),
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/questionnaires/{id}/responses",
     *     tags={"Questionnaires"},
     *     summary="Get all responses for a questionnaire",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getResponses(Request $request)
    {
        $questionnaire = Questionnaire::where('id', $request->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$questionnaire) {
            return response()->json([
                'title' => 'Questionnaire',
                'sub-title' => 'Questionnaire not found',
                'success' => false,
            ], 404);
        }

        $query = QuestionnaireResponse::where('questionnaire_id', $questionnaire->id)
            ->with(['retailer', 'respondedBy', 'questionResponses.question']);

        // Filter by retailer if provided
        if ($request->filled('retailer_id')) {
            $query->where('retailer_id', $request->retailer_id);
        }

        // Filter by date range if provided
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $responses = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'title' => 'Questionnaire Responses',
            'sub-title' => 'Responses fetched successfully',
            'success' => true,
            'data' => $responses,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/retailers/{retailer_id}/questionnaire-scores",
     *     tags={"Questionnaires"},
     *     summary="Get questionnaire scores for a retailer",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getRetailerScores(Request $request, $retailer_id)
    {
        $responses = QuestionnaireResponse::where('retailer_id', $retailer_id)
            ->where('company_id', $request->company->id)
            ->with(['questionnaire', 'respondedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        $averageScore = $responses->avg('score_percentage');

        return response()->json([
            'title' => 'Retailer Questionnaire Scores',
            'sub-title' => 'Scores fetched successfully',
            'success' => true,
            'data' => [
                'responses' => $responses,
                'average_score' => round($averageScore, 2),
                'total_responses' => $responses->count(),
            ],
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/questionnaires/active",
     *     tags={"Questionnaires"},
     *     summary="Get all active questionnaires for mobile use",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getActive(Request $request)
    {
        $questionnaires = Questionnaire::where('company_id', $request->company->id)
            ->where('is_active', true)
            ->where('is_deleted', false)
            ->with(['activeQuestions'])
            ->get();

        return response()->json([
            'title' => 'Active Questionnaires',
            'sub-title' => 'Active questionnaires fetched successfully',
            'success' => true,
            'data' => $questionnaires,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/questionnaires/my-responses",
     *     tags={"Questionnaires"},
     *     summary="Get current user's questionnaire responses",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getMyResponses(Request $request)
    {
        $responses = QuestionnaireResponse::where('company_id', $request->company->id)
            ->where('responded_by', auth()->id())
            ->with(['questionnaire', 'retailer', 'questionResponses.question'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'title' => 'My Questionnaire Responses',
            'sub-title' => 'Responses fetched successfully',
            'success' => true,
            'data' => $responses,
        ], 200);
    }
}
