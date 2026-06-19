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
        Schema::table('markets', function (Blueprint $table) {
            $table->dropForeign('markets_base_currency_foreign');
            $table->dropForeign('markets_quote_currency_foreign');

            $table->foreign('base_currency')
                ->references('symbol')
                ->on('currencies')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('quote_currency')
                ->references('symbol')
                ->on('currencies')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('markets', function (Blueprint $table) {
            $table->dropForeign('markets_base_currency_foreign');
            $table->dropForeign('markets_quote_currency_foreign');

            $table->foreign('base_currency')
                ->references('symbol')
                ->on('currencies')
                ->cascadeOnDelete();

            $table->foreign('quote_currency')
                ->references('symbol')
                ->on('currencies')
                ->cascadeOnDelete();
        });
    }
};
