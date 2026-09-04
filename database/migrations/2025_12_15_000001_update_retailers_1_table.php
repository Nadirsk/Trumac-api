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
            $table->string('image', 255)->nullable()->after('shop_name')->comment('Store/Shop image path');
            $table->string('city', 100)->nullable()->after('address')->comment('City name');
            $table->string('pincode', 10)->nullable()->after('city')->comment('Postal/ZIP code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retailers', function (Blueprint $table) {
            $table->dropColumn(['image', 'city', 'pincode']);
        });
    }
};
