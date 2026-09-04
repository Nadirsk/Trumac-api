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
        Schema::create('cash_collections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('retailer_id');
            $table->unsignedBigInteger('sales_order_id')->nullable();
            $table->decimal('amount_due', 12, 2)->default(0);
            $table->decimal('amount_collected', 12, 2)->default(0);
            $table->unsignedBigInteger('collected_by')->nullable();
            $table->date('collection_date');
            $table->enum('status', ['pending', 'partial', 'collected', 'rescheduled', 'escalated'])->default('pending');
            $table->integer('reschedule_count')->default(0);
            $table->date('next_collection_date')->nullable();
            $table->text('reschedule_reason')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('payment_mode')->nullable()->comment('cash, upi, cheque, bank_transfer');
            $table->string('payment_reference')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();

            $table->index(['retailer_id']);
            $table->index(['sales_order_id']);
            $table->index(['collected_by']);
            $table->index(['collection_date']);
            $table->index(['status']);
            $table->index(['company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_collections');
    }
};
