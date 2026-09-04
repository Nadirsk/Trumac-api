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
        Schema::table('requisitions', function (Blueprint $table) {
            $table->unsignedBigInteger('driver_id')->nullable()->after('approved_at')->comment('Assigned driver for delivery');
            $table->string('vehicle_number', 50)->nullable()->after('driver_id')->comment('Vehicle used for delivery');
            $table->text('fulfillment_notes')->nullable()->after('vehicle_number')->comment('Notes about fulfillment/delivery');
            $table->unsignedBigInteger('journey_plan_id')->nullable()->after('fulfillment_notes')->comment('Associated journey plan');

            $table->foreign('driver_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('journey_plan_id')->references('id')->on('journey_plans')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->dropForeign(['journey_plan_id']);
            $table->dropColumn(['driver_id', 'vehicle_number', 'fulfillment_notes', 'journey_plan_id']);
        });
    }
};
