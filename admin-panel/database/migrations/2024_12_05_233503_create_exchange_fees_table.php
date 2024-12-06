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
        Schema::create('exchange_profits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exchange_id')->constrained('exchanges')->onDelete('cascade'); // Foreign key reference to exchanges
            $table->foreignId('market_id')->constrained('markets')->onDelete('cascade'); // Foreign key reference to exchanges
            $table->string('fee_type'); // Type of fee (e.g., "buy", "sell")
            $table->decimal('fee_percentage', 5, 4); // The fee percentage (e.g., 0.01 for 1%)
            $table->decimal('fixed_amount', 5, 4); // The fee percentage (e.g., 0.01 for 1%)
            $table->timestamp('effective_date');
            $table->timestamps();
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
