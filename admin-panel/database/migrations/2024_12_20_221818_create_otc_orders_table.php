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
        Schema::create('otc_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('market_id')->constrained('markets'); // USDT in this case
            $table->decimal('quantity', 18, 8); // Quantity of BTC the user receives
            $table->decimal('price', 18, 8); // Swap price (e.g., BTC/USDT rate)
            $table->decimal('fee', 18, 8)->nullable(); // Optional transaction fee
            $table->enum('type', ['buy', 'sell']); // NEW: Indicates whether it’s a buy or sell
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otc_orders');
    }
};
