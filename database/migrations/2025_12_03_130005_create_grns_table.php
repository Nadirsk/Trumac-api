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
        Schema::create('grns', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number')->unique();
            $table->string('reference_type')->comment('purchase_order, requisition, return');
            $table->unsignedBigInteger('reference_id');
            $table->string('location_type')->comment('warehouse, company_godown, franchise, head_office');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->date('received_date');
            $table->enum('status', ['draft', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->string('driver_name')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();

            $table->index(['grn_number']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['location_type', 'location_id']);
            $table->index(['status']);
            $table->index(['received_date']);
            $table->index(['company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grns');
    }
};
