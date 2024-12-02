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
            $table->string('base_currency'); // e.g., BTC
            $table->foreign('base_currency')->references('symbol')->on('currencies')->onDelete('cascade');

            $table->string('quote_currency'); // e.g., USD
            $table->foreign('quote_currency')->references('symbol')->on('currencies')->onDelete('cascade');

            $table->decimal('min_trade_amount', 18, 8)->default(0);
            $table->decimal('max_trade_amount', 18, 8)->default(0);

            $table->decimal('price', 18, 2)->default(0);
            $table->decimal('exchange_price', 18, 2)->default(0);
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
