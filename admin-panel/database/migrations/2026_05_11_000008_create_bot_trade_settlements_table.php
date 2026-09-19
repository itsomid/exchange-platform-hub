<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_trade_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('bot_buy_execution_id')->constrained('bot_buy_executions')->onDelete('restrict');
            $table->foreignId('bot_sell_order_id')->constrained('bot_sell_orders')->onDelete('restrict');
            $table->decimal('gross_revenue', 20, 8)->default(0);
            $table->decimal('cost_basis', 20, 8)->default(0);
            $table->decimal('network_fee', 20, 8)->default(0);
            $table->decimal('exchange_fee', 20, 8)->default(0);
            $table->decimal('performance_fee', 20, 8)->default(0);
            $table->decimal('cancel_fee', 20, 8)->default(0);
            $table->decimal('net_pnl', 20, 8)->default(0);
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_trade_settlements');
    }
};
