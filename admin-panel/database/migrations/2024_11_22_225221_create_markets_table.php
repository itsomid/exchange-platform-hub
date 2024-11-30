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
        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('base_currency_id')->constrained('currencies')->onDelete('cascade'); // e.g., BTC
            $table->foreignId('quote_currency_id')->constrained('currencies')->onDelete('cascade'); // e.g., USD

            $table->decimal('min_trade_amount', 18, 8)->default(0);
            $table->decimal('max_trade_amount', 18, 8)->default(0);

            $table->string('symbol'); // Market symbol (e.g., USDT_BTC)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('markets');
    }
};
