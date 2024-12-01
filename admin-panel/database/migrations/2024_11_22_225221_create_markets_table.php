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
            $table->string('base_currency_symbol'); // e.g., BTC
            $table->foreign('base_currency_symbol')->references('symbol')->on('currencies')->onDelete('cascade');

            $table->string('quote_currency_symbol'); // e.g., USD
            $table->foreign('quote_currency_symbol')->references('symbol')->on('currencies')->onDelete('cascade');

            $table->decimal('min_trade_amount', 18, 8)->default(0);
            $table->decimal('max_trade_amount', 18, 8)->default(0);

            $table->decimal('price', 18, 8)->default(0);
            $table->decimal('exchange_price', 18, 8)->default(0);
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
