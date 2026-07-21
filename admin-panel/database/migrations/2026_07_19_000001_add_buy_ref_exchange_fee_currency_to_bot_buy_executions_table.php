<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_buy_executions', function (Blueprint $table) {
            $table->string('buy_ref_exchange_fee_currency', 16)
                ->nullable()
                ->after('buy_ref_exchange_fee')
                ->comment('Asset symbol CoinEx charged the buy fee in, e.g. NEO or USDT');
        });
    }

    public function down(): void
    {
        Schema::table('bot_buy_executions', function (Blueprint $table) {
            $table->dropColumn('buy_ref_exchange_fee_currency');
        });
    }
};
