<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\softDeletes;

class newExample extends Model
{
    use HasFactory, softDeletes;
    public static $searchable = SearchModelParams::newExample;

    protected $fillable = [
        'name',
        'description',
    ];

    public $accessable_columns = [
        'name',
        'description',
    ];
}