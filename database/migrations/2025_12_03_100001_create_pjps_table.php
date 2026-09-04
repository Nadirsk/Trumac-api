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
        Schema::create('pjps', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true)->comment('0 = Inactive, 1 = Active');
            $table->boolean('is_deleted')->default(false);
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable()->comment('User assigned to this PJP');
            $table->unsignedBigInteger('franchise_id')->nullable()->comment('Franchise area for this PJP');
            $table->tinyInteger('day_of_week')->comment('1=Monday, 2=Tuesday, ..., 7=Sunday');
            $table->string('name')->nullable()->comment('Optional PJP name/label');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id']);
            $table->index(['employee_id']);
            $table->index(['franchise_id']);
            $table->index(['day_of_week']);
            $table->unique(['employee_id', 'day_of_week', 'is_deleted'], 'unique_employee_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pjps');
    }
};
