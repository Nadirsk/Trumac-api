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
        Schema::create('pjp_retailers', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true)->comment('0 = Inactive, 1 = Active');
            $table->unsignedBigInteger('pjp_id');
            $table->unsignedBigInteger('retailer_id');
            $table->integer('sequence')->default(1)->comment('Visit order/sequence');
            $table->time('planned_visit_time')->nullable()->comment('Expected visit time');
            $table->integer('expected_duration')->nullable()->comment('Expected visit duration in minutes');
            $table->timestamps();

            $table->index(['pjp_id']);
            $table->index(['retailer_id']);
            $table->unique(['pjp_id', 'retailer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pjp_retailers');
    }
};
