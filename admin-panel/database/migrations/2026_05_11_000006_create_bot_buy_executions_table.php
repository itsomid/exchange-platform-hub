<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_buy_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_order_id')->constrained('bot_orders')->onDelete('cascade');
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('restrict');
            $table->json('signal_snapshot'); // {D_i, E_i, C_i, priority, weight}
            $table->unsignedTinyInteger('original_sell_orders_count')->nullable()->comment('Number of sell targets originally defined in the signal');
            $table->unsignedTinyInteger('effective_sell_orders_count')->nullable()->comment('Number of sell targets after smart-collapse (may be < original)');
            $table->decimal('allocated_usdt', 20, 8);
            $table->decimal('filled_amount', 20, 8)->default(0);
            $table->decimal('avg_buy_price', 18, 8)->nullable();
            $table->decimal('buy_ref_exchange_fee', 20, 8)->default(0);
            $table->decimal('network_fee', 20, 8)->default(0);
            $table->unsignedBigInteger('exchange_transaction_id')->nullable();
            $table->unsignedBigInteger('exchange_order_id')->nullable()->index(); // Market-buy order ID returned by reference exchange (CoinEx)
            $table->string('status')->default('PENDING'); // PENDING|BOUGHT|FAILED
            $table->timestamp('created_at')->nullable();

            $table->foreign('exchange_transaction_id')
                ->references('id')
                ->on('exchange_transactions')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_buy_executions');
    }
};
