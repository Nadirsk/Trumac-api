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
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();

            // Reference to GRN
            $table->unsignedBigInteger('grn_id');
            $table->foreign('grn_id')->references('id')->on('grns')->onDelete('cascade');

            // Reference to Requisition (optional)
            $table->unsignedBigInteger('requisition_id')->nullable();
            $table->foreign('requisition_id')->references('id')->on('requisitions')->onDelete('set null');

            // From location (source - who sent the goods)
            $table->string('from_location_type', 50);
            $table->unsignedBigInteger('from_location_id')->nullable();

            // To location (destination - who received the goods)
            $table->string('to_location_type', 50);
            $table->unsignedBigInteger('to_location_id')->nullable();

            // Invoice details
            $table->date('invoice_date');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);

            // Status: generated, sent, paid
            $table->string('status', 20)->default('generated');

            // PDF path
            $table->string('pdf_path')->nullable();

            $table->text('notes')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->unsignedBigInteger('company_id');

            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['invoice_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};
