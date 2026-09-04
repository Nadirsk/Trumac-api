<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionnaireResponse extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::QuestionnaireResponse;

    protected $fillable = [
        'questionnaire_id',
        'retailer_id',
        'responded_by',
        'total_score',
        'max_possible_score',
        'score_percentage',
        'remarks',
        'company_id',
    ];

    protected $casts = [
        'total_score' => 'decimal:2',
        'max_possible_score' => 'decimal:2',
        'score_percentage' => 'decimal:2',
    ];

    // Relationships
    public function questionnaire()
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function retailer()
    {
        return $this->belongsTo(Retailer::class);
    }

    public function respondedBy()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function questionResponses()
    {
        return $this->hasMany(QuestionResponse::class);
    }

    // Scopes
    public function scopeForRetailer($query, $retailerId)
    {
        return $query->where('retailer_id', $retailerId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('responded_by', $userId);
    }

    // Methods
    public function calculateScores()
    {
        $totalScore = $this->questionResponses()->sum('score');
        $maxPossible = $this->questionnaire->getMaxPossibleScore();
        $percentage = $maxPossible > 0 ? ($totalScore / $maxPossible) * 100 : 0;

        $this->update([
            'total_score' => $totalScore,
            'max_possible_score' => $maxPossible,
            'score_percentage' => round($percentage, 2),
        ]);

        // Update retailer's rating based on all questionnaire responses
        $this->updateRetailerRating();

        return $this;
    }

    /**
     * Update the retailer's rating based on average of all questionnaire scores
     */
    public function updateRetailerRating()
    {
        if (!$this->retailer_id) {
            return;
        }

        // Calculate average score_percentage from all questionnaire responses for this retailer
        $averagePercentage = self::where('retailer_id', $this->retailer_id)
            ->whereNotNull('score_percentage')
            ->avg('score_percentage');

        // Convert percentage (0-100) to rating (0-5)
        $rating = $averagePercentage ? round(($averagePercentage / 100) * 5, 2) : 0;

        // Update retailer's rating
        Retailer::where('id', $this->retailer_id)->update(['rating' => $rating]);
    }
}
