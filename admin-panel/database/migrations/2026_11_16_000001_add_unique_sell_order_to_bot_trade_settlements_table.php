<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A sell tier is settled exactly once. Parallel admin cancel-all runs once
 * settled the same tier two or three times; this makes the database refuse it.
 */
return new class extends Migration
{
    public function up(): void
    {
        $duplicated = DB::table('bot_trade_settlements')
            ->whereNotNull('bot_sell_order_id')
            ->groupBy('bot_sell_order_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('bot_sell_order_id');

        if ($duplicated->isNotEmpty()) {
            $users = DB::table('bot_trade_settlements')
                ->whereIn('bot_sell_order_id', $duplicated)
                ->distinct()
                ->pluck('user_id')
                ->implode(', ');

            throw new RuntimeException(
                "{$duplicated->count()} sell orders still have more than one settlement (users: {$users}). "
                .'Run `php artisan bot:fix-duplicate-settlements --user=<id> ... --execute` first.'
            );
        }

        Schema::table('bot_trade_settlements', function (Blueprint $table) {
            $table->unique('bot_sell_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('bot_trade_settlements', function (Blueprint $table) {
            $table->dropUnique(['bot_sell_order_id']);
        });
    }
};
