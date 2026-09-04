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
        Schema::create('driver_documents', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true)->comment('0 = Inactive, 1 = Active');
            $table->boolean('is_deleted')->default(false)->comment('Used for soft delete');
            $table->integer('company_id')->nullable()->comment('From Company table');
            $table->integer('user_id')->nullable()->comment('From User table');
            $table->integer('doc_type_id')->nullable()->comment('From ValueList table');
            $table->string('doc_front_path', 100)->nullable();
            $table->string('doc_back_path', 100)->nullable();
            $table->string('doc_number', 100)->nullable();
            $table->boolean('status')->default(false)->comment('0. Pending, 1. Approved, 2. Rejected');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_documents');
    }
};
