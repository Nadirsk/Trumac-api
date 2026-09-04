<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'questionnaire_response_id',
        'question_id',
        'answer',
        'score',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    // Relationships
    public function questionnaireResponse()
    {
        return $this->belongsTo(QuestionnaireResponse::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
