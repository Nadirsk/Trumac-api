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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true)->comment('0 = Inactive, 1 = Active');
            $table->boolean('is_deleted')->default(false)->comment('Used for soft delete');
            $table->string('name', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->bigInteger('phone')->nullable();
            $table->string('admin_name', 100)->nullable();
            $table->longText('address')->nullable();
            $table->integer('city_id')->nullable()->comment('From City table');
            $table->integer('state_id')->nullable()->comment('From State table');
            $table->string('pincode', 10)->nullable();
            $table->longText('about_us')->nullable();
            $table->longText('terms_conditions')->nullable();
            $table->longText('privacy_policy')->nullable();
            $table->longText('faqs')->nullable();
            $table->longText('disclaimer')->nullable();
            $table->string('logo_path', 100)->nullable();
            $table->string('url', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
