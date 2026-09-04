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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sku_id');
            $table->string('from_location_type')->nullable()->comment('warehouse, company_godown, franchise, head_office, vendor');
            $table->unsignedBigInteger('from_location_id')->nullable();
            $table->string('to_location_type')->nullable()->comment('warehouse, company_godown, franchise, head_office, retailer');
            $table->unsignedBigInteger('to_location_id')->nullable();
            $table->decimal('quantity', 12, 2);
            $table->string('movement_type')->comment('purchase, sale, transfer, adjustment, return, damage, expiry');
            $table->string('reference_type')->nullable()->comment('purchase_order, sales_order, requisition, grn, adjustment');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('batch_number')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();

            $table->index(['sku_id']);
            $table->index(['from_location_type', 'from_location_id']);
            $table->index(['to_location_type', 'to_location_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['movement_type']);
            $table->index(['created_at']);
            $table->index(['company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
