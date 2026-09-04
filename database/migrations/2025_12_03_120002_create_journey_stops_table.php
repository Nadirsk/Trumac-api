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
        Schema::create('journey_stops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journey_plan_id');
            $table->string('stop_type')->comment('delivery, pickup, retailer, warehouse, godown, franchise');
            $table->string('reference_type')->nullable()->comment('sales_order, requisition, retailer, etc');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->integer('sequence')->default(0)->comment('Order of stops');
            $table->string('name')->nullable()->comment('Stop name/address');
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->time('planned_time')->nullable();
            $table->timestamp('arrival_time')->nullable();
            $table->timestamp('departure_time')->nullable();
            $table->decimal('arrival_latitude', 10, 7)->nullable();
            $table->decimal('arrival_longitude', 10, 7)->nullable();
            $table->decimal('departure_latitude', 10, 7)->nullable();
            $table->decimal('departure_longitude', 10, 7)->nullable();
            $table->string('signature_path')->nullable()->comment('Path to signature image');
            $table->enum('status', ['pending', 'arrived', 'completed', 'skipped'])->default('pending');
            $table->text('notes')->nullable();
            $table->text('skip_reason')->nullable();
            $table->timestamps();

            $table->index(['journey_plan_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journey_stops');
    }
};
