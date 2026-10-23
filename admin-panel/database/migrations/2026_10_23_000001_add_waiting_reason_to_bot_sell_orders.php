<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Why a WAITING sell tier is not on Binance yet: its price is above the
 * PERCENT_PRICE_BY_SIDE ceiling (price_ceiling) or the symbol's open-order
 * limit MAX_NUM_ORDERS is used up (order_limit).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_sell_orders', function (Blueprint $table) {
            $table->string('waiting_reason', 16)->nullable()->after('placement_status');
        });

        DB::table('bot_sell_orders')
            ->where('placement_status', 'WAITING')
            ->update(['waiting_reason' => DB::raw(
                "CASE WHEN last_placement_error LIKE '%MAX_NUM_ORDERS%' THEN 'order_limit' ELSE 'price_ceiling' END"
            )]);
    }

    public function down(): void
    {
        Schema::table('bot_sell_orders', function (Blueprint $table) {
            $table->dropColumn('waiting_reason');
        });
    }
};
