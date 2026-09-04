<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            // Make grn_id nullable so SO-based invoices can be created without GRN
            $table->dropForeign(['grn_id']);
            $table->unsignedBigInteger('grn_id')->nullable()->change();
            $table->foreign('grn_id')->references('id')->on('grns')->onDelete('set null');

            // Make from/to location nullable for SO-based invoices
            $table->string('from_location_type', 50)->nullable()->change();
            $table->string('to_location_type', 50)->nullable()->change();

            // Add sales_order_id reference
            $table->unsignedBigInteger('sales_order_id')->nullable()->after('requisition_id');
            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->onDelete('set null');

            // Add retailer_id for SO-based invoices
            $table->unsignedBigInteger('retailer_id')->nullable()->after('sales_order_id');
            $table->foreign('retailer_id')->references('id')->on('retailers')->onDelete('set null');

            $table->index(['sales_order_id']);
            $table->index(['retailer_id']);
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropForeign(['sales_order_id']);
            $table->dropForeign(['retailer_id']);
            $table->dropColumn(['sales_order_id', 'retailer_id']);
        });
    }
};
