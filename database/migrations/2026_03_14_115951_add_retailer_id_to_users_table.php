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
            $table->unsignedBigInteger('retailer_id')->nullable()->after('franchise_id')->comment('Assigned retailer for User (Retailer) role users');

            $table->foreign('retailer_id')->references('id')->on('retailers')->onDelete('set null');
            $table->index(['retailer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['retailer_id']);
            $table->dropIndex(['users_retailer_id_index']);
            $table->dropColumn('retailer_id');
        });
    }
};
