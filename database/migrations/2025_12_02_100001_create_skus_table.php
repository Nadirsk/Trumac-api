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
        Schema::create('skus', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true)->comment('0 = Inactive, 1 = Active');
            $table->boolean('is_deleted')->default(false)->comment('Used for soft delete');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('code', 50)->nullable();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('unit', 20)->nullable()->comment('e.g., pcs, kg, ltr, box');
            $table->decimal('mrp', 10, 2)->nullable()->comment('Maximum Retail Price');
            $table->decimal('selling_price', 10, 2)->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->string('hsn_code', 20)->nullable()->comment('HSN/SAC Code for GST');
            $table->decimal('gst_percent', 5, 2)->default(0)->comment('GST percentage');
            $table->integer('out_of_stock_threshold')->default(10)->comment('Alert when qty falls below');
            $table->string('barcode', 50)->nullable();
            $table->timestamps();

            $table->index(['company_id']);
            $table->index(['category_id']);
            $table->index(['code']);
            $table->index(['name']);
            $table->index(['barcode']);
        });

        Schema::create('sku_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sku_id');
            $table->string('image_path', 255);
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['sku_id']);
            $table->foreign('sku_id')->references('id')->on('skus')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sku_images');
        Schema::dropIfExists('skus');
    }
};
