<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_sell_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_buy_execution_id')->constrained('bot_buy_executions')->onDelete('cascade');
            $table->unsignedBigInteger('exchange_order_id')->nullable()->index(); // Order ID returned by reference exchange (CoinEx)
            $table->string('target_type');           // percent|price
            $table->decimal('target_value', 18, 8);
            $table->decimal('share_percent', 5, 2);
            $table->decimal('amount_to_sell', 20, 8);
            $table->decimal('sell_ref_exchange_fee', 20, 8)->default(0);
            $table->string('status')->default('OPEN'); // OPEN|FILLED|CANCELED
            $table->timestamp('filled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_sell_orders');
    }
};
