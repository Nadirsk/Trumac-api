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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true)->comment('0 = Inactive, 1 = Active');
            $table->softDeletes();
            $table->integer('position_id')->nullable()->comment('From Positions table');
            $table->unsignedBigInteger('clinic_id')->nullable()->comment('From Clinic table');
            $table->unsignedBigInteger('lab_id')->nullable()->comment('From Lab table');
            $table->string('api_token', 60)->unique()->nullable();
            $table->string('referral_code', 100)->nullable();
            $table->string('first_name', 100)->nullable();
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('user_name', 100)->nullable();
            $table->bigInteger('phone')->nullable();
            $table->string('email', 100)->unique()->nullable();
            $table->string('password', 100)->nullable();
            $table->string('soft_password', 20)->nullable()->comment('Decrypted Password');
            $table->string('gender', 100)->nullable();
            $table->string('dob', 100)->nullable();
            $table->longText('address')->nullable();
            $table->integer('city_id')->nullable()->comment('From City table');
            $table->integer('state_id')->nullable()->comment('From State table');
            $table->integer('pincode')->nullable();
            $table->string('image_path', 100)->nullable();
            $table->bigInteger('otp')->nullable()->comment('For OTP Verification');
            $table->string('wallet', 100)->nullable();
            $table->integer('referred_user_id')->nullable()->comment('From User table');
            $table->string('referred_user_code', 100)->nullable()->comment('From Reffered User table');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
