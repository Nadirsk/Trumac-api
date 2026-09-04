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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sku_id');
            $table->string('location_type')->comment('warehouse, company_godown, franchise, head_office');
            $table->unsignedBigInteger('location_id');
            $table->decimal('quantity', 12, 2)->default(0)->comment('Available quantity');
            $table->decimal('reserved_quantity', 12, 2)->default(0)->comment('Reserved for orders');
            $table->decimal('min_quantity', 12, 2)->default(0)->comment('Minimum stock level for alerts');
            $table->decimal('max_quantity', 12, 2)->nullable()->comment('Maximum stock level');
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();

            $table->index(['sku_id']);
            $table->index(['location_type', 'location_id']);
            $table->index(['company_id']);
            $table->unique(['sku_id', 'location_type', 'location_id', 'batch_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
