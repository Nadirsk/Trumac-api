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
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->unsignedBigInteger('retailer_id');
            $table->string('source_type')->nullable()->comment('franchise, cg, warehouse');
            $table->unsignedBigInteger('source_id')->nullable()->comment('franchise_id, cg_id, warehouse_id');
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'processing', 'dispatched', 'delivered', 'cancelled', 'returned', 'no_order'])->default('pending');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('no_order_reason')->nullable()->comment('Reason when status is no_order: shop_closed, sufficient_stock, payment_issues, quality_complaints, etc.');
            $table->text('cancellation_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('dispatched_by')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->unsignedBigInteger('delivered_by')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();

            $table->index(['retailer_id']);
            $table->index(['source_type', 'source_id']);
            $table->index(['status']);
            $table->index(['order_date']);
            $table->index(['company_id']);
            $table->index(['created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
