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
        Schema::create('user_timestamps', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->comment('From Company table');
            $table->integer('user_id')->comment('From User table');
            $table->string('url', 100)->nullable();
            $table->string('name', 100)->nullable();
            $table->string('timespent', 100)->nullable();
            $table->json('old_json')->nullable();
            $table->json('new_json')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_timestamps');
    }
};
