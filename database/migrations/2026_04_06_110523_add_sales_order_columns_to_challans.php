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
        Schema::table('challans', function (Blueprint $table) {
            $table->unsignedBigInteger('sales_order_id')->nullable()->after('requisition_id');
        });

        Schema::table('challan_items', function (Blueprint $table) {
            $table->unsignedBigInteger('sales_order_item_id')->nullable()->after('requisition_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('challans', function (Blueprint $table) {
            $table->dropColumn('sales_order_id');
        });
        Schema::table('challan_items', function (Blueprint $table) {
            $table->dropColumn('sales_order_item_id');
        });
    }
};
