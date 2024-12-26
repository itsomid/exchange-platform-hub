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
        Schema::create('exchange_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_id')->constrained('markets')->onDelete('cascade'); // Reference to markets table
            $table->foreignId('exchange_id')->constrained('exchanges')->onDelete('cascade'); // Reference to exchanges table
            $table->decimal('price', 18, 8)->default(0); // Price of the market on that exchange
            $table->decimal('open_price', 18, 8)->default(0); // Opening price on the exchange
            $table->decimal('exchange_profit_sell', 5, 4)->default(0); // Profit margin or fee for the exchange
            $table->decimal('exchange_profit_buy', 5, 4)->default(0); // Profit margin or fee for the exchange
            $table->timestamps();

            // Index the foreign key columns for faster joins and lookups
            $table->index('market_id');
            $table->index('exchange_id');
            $table->index(['market_id', 'exchange_id']);

            // Optional: Composite index on market_id and exchange_id for common queries
            $table->unique(['market_id']); // This ensures that there is only one price per market-exchange pair.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_profits');
    }
};
