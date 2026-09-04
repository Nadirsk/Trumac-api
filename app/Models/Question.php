<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::Question;

    protected $fillable = [
        'questionnaire_id',
        'question_text',
        'type',
        'options',
        'weight',
        'sequence',
        'is_required',
        'is_active',
        'is_deleted',
    ];

    protected $casts = [
        'options' => 'array',
        'weight' => 'integer',
        'sequence' => 'integer',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    // Question types
    const TYPE_TEXT = 'text';
    const TYPE_SINGLE_CHOICE = 'single_choice';
    const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    const TYPE_RATING = 'rating';
    const TYPE_YES_NO = 'yes_no';

    // Relationships
    public function questionnaire()
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function responses()
    {
        return $this->hasMany(QuestionResponse::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_deleted', false);
    }

    // Methods
    public function calculateScore($answer)
    {
        // Default scoring logic based on question type
        switch ($this->type) {
            case self::TYPE_YES_NO:
                return strtolower($answer) === 'yes' ? $this->weight : 0;

            case self::TYPE_RATING:
                // Assuming rating is 1-5
                $rating = (int) $answer;
                return ($rating / 5) * $this->weight;

            case self::TYPE_SINGLE_CHOICE:
            case self::TYPE_MULTIPLE_CHOICE:
                // Check if answer matches correct option (if defined in options)
                if ($this->options && isset($this->options['correct'])) {
                    if (is_array($answer)) {
                        $correct = count(array_intersect($answer, (array) $this->options['correct']));
                        return ($correct / count($this->options['correct'])) * $this->weight;
                    }
                    return $answer === $this->options['correct'] ? $this->weight : 0;
                }
                // If no correct answer defined, give full score for any answer
                return $this->weight;

            case self::TYPE_TEXT:
            default:
                // Text answers get full weight if answered
                return !empty($answer) ? $this->weight : 0;
        }
    }
}
