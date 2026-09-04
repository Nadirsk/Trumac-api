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
        Schema::create('challans', function (Blueprint $table) {
            $table->id();
            $table->string('challan_number', 50)->unique();
            $table->unsignedBigInteger('requisition_id')->nullable();
            $table->unsignedBigInteger('journey_plan_id')->nullable();

            // From location
            $table->string('from_location_type', 50)->comment('head_office, warehouse, company_godown');
            $table->unsignedBigInteger('from_location_id')->default(0);

            // To location
            $table->string('to_location_type', 50)->comment('warehouse, company_godown, franchise');
            $table->unsignedBigInteger('to_location_id');

            // Driver & Vehicle
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('vehicle_number', 50)->nullable();

            // Status
            $table->enum('status', ['generated', 'in_transit', 'delivered', 'received', 'cancelled'])
                  ->default('generated');

            // Delivery details
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('received_by', 100)->nullable();
            $table->text('receiver_signature')->nullable()->comment('Base64 signature image');
            $table->text('delivery_proof_image')->nullable()->comment('Photo proof path');

            // Notes
            $table->text('notes')->nullable();
            $table->text('delivery_notes')->nullable();

            // Meta
            $table->unsignedBigInteger('created_by')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->unsignedBigInteger('company_id');
            $table->timestamps();

            // Indexes
            $table->index('challan_number');
            $table->index('requisition_id');
            $table->index('journey_plan_id');
            $table->index('driver_id');
            $table->index('status');
            $table->index('company_id');

            // Foreign keys
            $table->foreign('requisition_id')->references('id')->on('requisitions')->onDelete('set null');
            $table->foreign('journey_plan_id')->references('id')->on('journey_plans')->onDelete('set null');
            $table->foreign('driver_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challans');
    }
};
