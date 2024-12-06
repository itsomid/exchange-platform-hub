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
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('mobile')->unique()->nullable();
            $table->string('national_id')->unique()->nullable();

            $table->enum('status', ['active', 'suspended', 'inactive'])->default('active');

            $table->decimal('transaction_limit', 18, 2)->default(0); // Transaction limit

            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('registration_date')->useCurrent(); // Registration date
            $table->string('two_factor_secret')->nullable(); // 2FA status
            $table->timestamp('last_login')->nullable(); // Last login
            $table->string('last_ip_address')->nullable(); // Last login IP address
            $table->string('device_info')->nullable(); // Device information

            $table->string('kyc_status')->default('approved'); // KYC Status
            $table->enum('user_type', ['individual', 'corporate'])->default('individual');
            $table->integer('profile_completeness')->default(0); // Profile completeness percentage
            $table->string('country')->nullable(); // Country of residence
            $table->text('support_description')->nullable();
            $table->text('description')->nullable();

            $table->softDeletes();
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
