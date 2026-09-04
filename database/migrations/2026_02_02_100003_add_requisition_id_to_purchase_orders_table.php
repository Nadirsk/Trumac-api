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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('requisition_id')->nullable()->after('po_number');
            $table->index('requisition_id');
            $table->foreign('requisition_id')->references('id')->on('requisitions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['requisition_id']);
            $table->dropIndex(['requisition_id']);
            $table->dropColumn('requisition_id');
        });
    }
};
