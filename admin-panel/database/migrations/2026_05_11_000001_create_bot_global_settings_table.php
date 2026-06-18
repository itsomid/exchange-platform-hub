<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_global_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_deposit_usdt', 20, 8)->default(20);

            $table->decimal('alpha_weight', 4, 2)->default(0.15);
            $table->unsignedTinyInteger('default_sell_orders_count')->default(3);
            $table->decimal('performance_fee_percent', 5, 2)->default(22);
            $table->decimal('p2p_min_order_value', 18, 8)->default(5)->comment('Minimum value (USDT-equivalent) for each P2P sell order');
            $table->json('transfer_fee_tiers');
            $table->boolean('is_enabled')->default(true);
            $table->boolean('cancel_sell_on_exchange_enabled')->default(true)
                ->comment('If true, cancelling a bot order market-sells the held coins on the reference exchange. If false, only fake settlement transactions are written.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_global_settings');
    }
};
