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
        Schema::create('requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('requisition_number')->unique();
            $table->string('from_location_type')->comment('head_office, warehouse - source to fulfill from');
            $table->unsignedBigInteger('from_location_id')->nullable();
            $table->string('to_location_type')->comment('warehouse, company_godown, franchise - destination requesting stock');
            $table->unsignedBigInteger('to_location_id');
            $table->enum('status', ['draft', 'pending', 'approved', 'partial', 'fulfilled', 'cancelled'])->default('draft');
            $table->date('request_date');
            $table->date('required_date')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();

            $table->index(['requisition_number']);
            $table->index(['from_location_type', 'from_location_id']);
            $table->index(['to_location_type', 'to_location_id']);
            $table->index(['status']);
            $table->index(['requested_by']);
            $table->index(['company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisitions');
    }
};
