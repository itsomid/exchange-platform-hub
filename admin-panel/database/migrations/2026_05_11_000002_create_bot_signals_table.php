<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('cascade');
            $table->unsignedInteger('priority')->default(999);
            $table->decimal('floor_price', 18, 8);
            $table->decimal('ceiling_price', 18, 8);
            $table->decimal('min_buy_amount_usdt', 18, 8)->default(5);
            $table->decimal('max_allocation_percent', 5, 2)->default(100);
            $table->decimal('p2p_min_order_value_override', 18, 8)
                  ->nullable()
                  ->comment('Override global p2p_min_order_value for this signal; NULL = use global');
            $table->unsignedTinyInteger('sell_orders_count')->default(3);
            $table->string('sell_mode')->default('percent'); // percent | price
            $table->json('sell_targets'); // [{trigger: 20, share: 50}, ...]
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('currency_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_signals');
    }
};
