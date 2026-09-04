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
        Schema::create('pjp_changes', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_deleted')->default(false);
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('pjp_id');
            $table->date('date')->comment('Date for which change is requested');
            $table->enum('action', ['add', 'remove', 'swap', 'reorder'])->comment('Type of change');
            $table->unsignedBigInteger('retailer_id')->nullable()->comment('Retailer being added/removed');
            $table->unsignedBigInteger('swap_retailer_id')->nullable()->comment('Retailer to swap with (for swap action)');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('remarks')->nullable()->comment('Approval/rejection remarks');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['company_id']);
            $table->index(['pjp_id']);
            $table->index(['date']);
            $table->index(['status']);
            $table->index(['requested_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pjp_changes');
    }
};
