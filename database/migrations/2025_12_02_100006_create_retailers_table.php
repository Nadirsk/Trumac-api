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
        Schema::create('retailers', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true)->comment('0 = Inactive, 1 = Active');
            $table->boolean('is_deleted')->default(false)->comment('Used for soft delete');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('franchise_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('name', 100)->comment('Owner/Contact name');
            $table->string('shop_name', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('registration_type', 20)->nullable()->comment('Registered or Unregistered');
            $table->string('gst_no', 20)->nullable();
            $table->string('pan_no', 20)->nullable();
            $table->decimal('rating', 3, 2)->default(0)->comment('Average rating 0-5');
            $table->boolean('is_flagged')->default(false)->comment('Flagged for collection issues');
            $table->decimal('credit_limit', 12, 2)->default(0)->comment('Maximum credit allowed');
            $table->decimal('outstanding_amount', 12, 2)->default(0)->comment('Current outstanding');
            $table->unsignedBigInteger('created_by')->nullable()->comment('User who created this retailer');
            $table->timestamp('last_order_date')->nullable()->comment('Date of last order placed');
            $table->unsignedInteger('total_orders')->default(0)->comment('Total number of orders placed');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['company_id']);
            $table->index(['franchise_id']);
            $table->index(['location_id']);
            $table->index(['shop_name']);
            $table->index(['phone']);
            $table->index(['is_flagged']);
            $table->index(['rating']);
            $table->index(['created_by']);
            $table->index(['last_order_date']);

            $table->foreign('franchise_id')->references('id')->on('franchises')->onDelete('set null');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
        });

        Schema::create('retailer_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('retailer_id');
            $table->unsignedBigInteger('rated_by')->nullable()->comment('User who gave the rating');
            $table->decimal('rating', 3, 2)->comment('Rating 0-5');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['retailer_id']);
            $table->foreign('retailer_id')->references('id')->on('retailers')->onDelete('cascade');
            $table->foreign('rated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retailer_ratings');
        Schema::dropIfExists('retailers');
    }
};
