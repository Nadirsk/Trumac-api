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
        Schema::create('company_godowns', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true)->comment('0 = Inactive, 1 = Active');
            $table->boolean('is_deleted')->default(false)->comment('Used for soft delete');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('name', 100);
            $table->string('code', 20)->nullable()->comment('Unique godown code');
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->integer('capacity')->nullable()->comment('Storage capacity in units');
            $table->timestamps();

            $table->index(['company_id']);
            $table->index(['warehouse_id']);
            $table->index(['location_id']);
            $table->index(['code']);

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_godowns');
    }
};
