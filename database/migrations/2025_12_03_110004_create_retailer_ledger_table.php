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
        Schema::create('retailer_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('retailer_id');
            $table->string('transaction_type')->comment('sale, collection, adjustment, opening');
            $table->string('reference_type')->nullable()->comment('sales_order, cash_collection');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('debit', 12, 2)->default(0)->comment('Amount owed by retailer');
            $table->decimal('credit', 12, 2)->default(0)->comment('Amount paid by retailer');
            $table->decimal('balance', 12, 2)->default(0)->comment('Running balance');
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();

            $table->index(['retailer_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['transaction_date']);
            $table->index(['company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retailer_ledger');
    }
};
