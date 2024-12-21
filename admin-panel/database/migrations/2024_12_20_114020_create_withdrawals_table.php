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
            $table->unsignedBigInteger('wallet_id'); // Foreign key
            $table->string('currency_chain'); // Blockchain (e.g., Ethereum, Binance Smart Chain)
            $table->string('currency_symbol'); // Token/Currency Symbol (e.g., BTC, ETH)
            $table->decimal('amount', 20, 8); // Withdrawal amount
            $table->string('address'); // Withdrawal address
            $table->string('transaction_hash', 64)->nullable();
            $table->string('status', 50); // Status of withdrawal (e.g., pending, completed, failed)
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
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
