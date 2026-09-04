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
        Schema::table('retailers', function (Blueprint $table) {
            $table->unsignedBigInteger('company_godown_id')->nullable()->after('franchise_id');

            // Add foreign key constraint
            $table->foreign('company_godown_id')
                ->references('id')
                ->on('company_godowns')
                ->onDelete('set null');

            // Add index for better query performance
            $table->index('company_godown_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retailers', function (Blueprint $table) {
            // Drop foreign key and index first
            $table->dropForeign(['company_godown_id']);
            $table->dropIndex(['company_godown_id']);

            // Drop the column
            $table->dropColumn('company_godown_id');
        });
    }
};
