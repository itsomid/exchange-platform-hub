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
        Schema::create('currency_chains', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('currency_id');
            $table->string('chain'); // The chain (e.g., BSC, TRC20, ERC20, etc.)
            $table->string('chain_name'); // The chain (e.g., BSC, TRC20, ERC20, etc.)
            $table->string('blockchain_name'); // The blockchain_name (e.g., BINANCE, TRON, ETHEREUM, etc.)
            $table->decimal('min_deposit_amount', 18, 8);  // Minimum deposit amount
            $table->decimal('min_withdraw_amount', 18, 8); // Minimum withdrawal amount
            $table->boolean('deposit_enabled')->default(true);  // Is deposit enabled for this chain
            $table->boolean('withdraw_enabled')->default(true); // Is withdrawal enabled for this chain
            $table->integer('deposit_delay_minutes')->default(0); // Deposit delay time (in minutes)
            $table->integer('safe_confirmations')->default(0);  // Number of confirmations for safe deposit
            //            $table->integer('irreversible_confirmations')->default(0); // Number of irreversible confirmations
            $table->decimal('network_fee', 18, 8);  // network fee for withdraw in this chain
            $table->decimal('exchange_withdrawal_fee', 18, 8)->default(0);  // Exchange fee for withdraw in this chain
            $table->integer('withdrawal_precision')->default(8); // Precision for withdrawal (decimal places)
            $table->string('memo')->nullable(); // Memo required for deposit (if any)
            $table->boolean('is_memo_required_for_deposit')->default(false); // Is memo required for deposit
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_chains');
    }
};
