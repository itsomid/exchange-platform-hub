<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_currency_cancellations', function (Blueprint $table) {
            // false: tiers in profit refund only the principal (no profit, no performance fee).
            $table->boolean('pay_profit')->default(true)->after('sell_on_exchange');
        });
    }

    public function down(): void
    {
        Schema::table('bot_currency_cancellations', function (Blueprint $table) {
            $table->dropColumn('pay_profit');
        });
    }
};
