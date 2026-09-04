<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Questionnaire extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::Questionnaire;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'is_deleted',
        'company_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('sequence');
    }

    public function activeQuestions()
    {
        return $this->hasMany(Question::class)
            ->where('is_active', true)
            ->where('is_deleted', false)
            ->orderBy('sequence');
    }

    public function responses()
    {
        return $this->hasMany(QuestionnaireResponse::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_deleted', false);
    }

    // Methods
    public function getMaxPossibleScore()
    {
        return $this->activeQuestions()->sum('weight') * 5; // Assuming max score per question is weight * 5
    }
}
