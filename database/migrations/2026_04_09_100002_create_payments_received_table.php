<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments_received', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('retailer_id');
            $table->date('payment_date');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_mode')->default('cash')->comment('cash, bank_transfer, upi, cheque');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('paid')->comment('paid, refunded');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->index(['invoice_id']);
            $table->index(['retailer_id']);
            $table->index(['payment_date']);
            $table->index(['payment_mode']);
            $table->index(['company_id']);
            $table->index(['created_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments_received');
    }
};
