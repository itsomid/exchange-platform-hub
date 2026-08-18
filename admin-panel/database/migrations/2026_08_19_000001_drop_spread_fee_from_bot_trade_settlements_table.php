<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('bot_trade_settlements', 'spread_fee')) {
            Schema::table('bot_trade_settlements', function (Blueprint $table) {
                $table->dropColumn('spread_fee');
            });
        }

        if (Schema::hasTable('transactions')) {
            DB::table('transactions')->where('subtype', 'bot_spread_fee')->delete();
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('bot_trade_settlements', 'spread_fee')) {
            Schema::table('bot_trade_settlements', function (Blueprint $table) {
                $table->decimal('spread_fee', 20, 8)->default(0);
            });
        }
    }
};
