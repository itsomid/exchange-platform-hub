<?php

use App\Models\Exchange;
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
            $table->foreign('base_currency')->references('symbol')->on('currencies')->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('quote_currency'); // e.g., USD
            $table->foreign('quote_currency')->references('symbol')->on('currencies')->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->decimal('min_trade_amount', 20, 8)->default(0);
            $table->decimal('max_trade_amount', 20, 8)->default(0);

            $table->decimal('min_otc_amount', 20, 8)->default(0);
            $table->decimal('max_otc_amount', 20, 8)->default(0);
            $table->boolean('ref_exchange_sell_enabled')->default(false);
            $table->boolean('price_update_enabled')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_home')->default(false);
            $table->timestamps();

            $table->unique(['base_currency', 'quote_currency']);

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
