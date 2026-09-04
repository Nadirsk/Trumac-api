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
        Schema::create('retailer_visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('IT Employee who visited');
            $table->unsignedBigInteger('retailer_id')->comment('Retailer visited');
            $table->unsignedBigInteger('pjp_id')->nullable()->comment('Related PJP if part of planned visit');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->date('visit_date')->comment('Date of visit');
            $table->dateTime('check_in_time')->nullable()->comment('Auto check-in timestamp');
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->dateTime('check_out_time')->nullable()->comment('Auto check-out timestamp');
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->comment('0 = Inactive, 1 = Active');
            $table->boolean('is_deleted')->default(false)->comment('0 = Not Deleted, 1 = Deleted');
            $table->timestamps();

            $table->index(['user_id', 'visit_date']);
            $table->index(['retailer_id', 'visit_date']);
            $table->index(['pjp_id']);
            $table->index(['company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retailer_visits');
    }
};
