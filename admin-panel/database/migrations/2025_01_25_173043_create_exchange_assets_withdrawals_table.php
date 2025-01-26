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
        Schema::create('exchange_assets_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('currency_symbol');
            $table->string('currency_chain');
            $table->decimal('amount', 20, 8);
            $table->string('exchange_transaction_id')->nullable();
            $table->string('hd_wallet_address');
            $table->timestamp('withdrawal_date');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_assets_withdrawals');
    }
};
