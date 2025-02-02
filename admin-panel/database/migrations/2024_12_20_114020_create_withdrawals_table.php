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
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('user_id'); // Foreign key
            $table->unsignedBigInteger('admin_id')->nullable(); // Foreign key
            $table->string(\'currency_chain'); // Blockchain (e.g., Ethereum, Binance Smart Chain)
            $table->string('currency_symbol'); // Token/Currency Symbol (e.g., BTC, ETH)
            $table->decimal('amount', 20, 8); // Withdrawal amount
            $table->decimal('total_fee', 20, 8)->default(0); // Withdraw Total fee (optional)
            $table->decimal('exchange_fee', 20, 8)->default(0); // Exchange fee (optional)
            $table->decimal('network_fee', 20, 8)->default(0); // Exchange fee (optional)
            $table->decimal('hd_wallet_network_fee', 20, 8)->default(0)->nullable(); // Exchange fee (optional)
            $table->decimal('usdt_value', 20, 8)->nullable();
            $table->string('address')->nullable(); // Withdrawal address
            $table->string('transaction_hash', 96)->nullable()->unique();
            $table->string('status', 50)->default('pending'); // Status of withdrawal (e.g., pending, completed, failed)
            $table->text('description')->nullable(); // Optional description
            $table->timestamp('confirmed_at')->nullable(); // Timestamp for confirmation
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
