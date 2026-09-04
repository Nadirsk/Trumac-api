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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('position_id')->comment('Assigned warehouse for Warehouse Admin role users');
            $table->unsignedBigInteger('company_godown_id')->nullable()->after('warehouse_id')->comment('Assigned company godown for CG Admin role users');
            $table->unsignedBigInteger('franchise_id')->nullable()->after('company_godown_id')->comment('Assigned franchise for Franchise Admin role users');

            // Add foreign key constraints
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->foreign('company_godown_id')->references('id')->on('company_godowns')->onDelete('set null');
            $table->foreign('franchise_id')->references('id')->on('franchises')->onDelete('set null');

            // Add indexes for better query performance
            $table->index(['warehouse_id']);
            $table->index(['company_godown_id']);
            $table->index(['franchise_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['company_godown_id']);
            $table->dropForeign(['franchise_id']);
            $table->dropColumn(['warehouse_id', 'company_godown_id', 'franchise_id']);
        });
    }
};
