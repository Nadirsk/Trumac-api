<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('questionnaire_id');
            $table->text('question_text');
            $table->string('type', 50)->default('text')->comment('text, single_choice, multiple_choice, rating, yes_no');
            $table->json('options')->nullable()->comment('Array of options for choice-based questions');
            $table->integer('weight')->default(1)->comment('Score weight for this question');
            $table->integer('sequence')->default(0)->comment('Order of question in questionnaire');
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true)->comment('0 = Inactive, 1 = Active');
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            $table->index(['questionnaire_id', 'sequence']);
            $table->index(['is_active', 'is_deleted']);

            $table->foreign('questionnaire_id')->references('id')->on('questionnaires')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
