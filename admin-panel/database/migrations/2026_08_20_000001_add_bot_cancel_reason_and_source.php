<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_sell_orders', function (Blueprint $table) {
            $table->string('cancel_reason')->nullable()->after('status');
        });

        Schema::table('bot_orders', function (Blueprint $table) {
            $table->string('cancel_source')->nullable()->after('status');
        });

        $this->backfillSellReasons();
        $this->backfillClosedExecutions();
    }

    public function down(): void
    {
        Schema::table('bot_sell_orders', function (Blueprint $table) {
            $table->dropColumn('cancel_reason');
        });

        Schema::table('bot_orders', function (Blueprint $table) {
            $table->dropColumn('cancel_source');
        });
    }

    private function backfillSellReasons(): void
    {
        $failedUnplaced = DB::table('bot_sell_orders as s')
            ->join('bot_buy_executions as e', 'e.id', '=', 's.bot_buy_execution_id')
            ->where('s.status', 'CANCELED')
            ->whereNull('s.cancel_reason')
            ->whereNull('s.exchange_order_id')
            ->where('e.status', 'FAILED')
            ->pluck('s.id');
        $this->setSellReason($failedUnplaced, 'sell_place_failed');

        $failedRollback = DB::table('bot_sell_orders as s')
            ->join('bot_buy_executions as e', 'e.id', '=', 's.bot_buy_execution_id')
            ->where('s.status', 'CANCELED')
            ->whereNull('s.cancel_reason')
            ->whereNotNull('s.exchange_order_id')
            ->where('e.status', 'FAILED')
            ->pluck('s.id');
        $this->setSellReason($failedRollback, 'sell_place_rollback');

        $exchangeSync = DB::table('bot_sell_orders as s')
            ->join('bot_buy_executions as e', 'e.id', '=', 's.bot_buy_execution_id')
            ->where('s.status', 'CANCELED')
            ->whereNull('s.cancel_reason')
            ->whereNotNull('s.exchange_order_id')
            ->where('e.status', 'BOUGHT')
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('bot_trade_settlements')
                    ->whereColumn('bot_trade_settlements.bot_sell_order_id', 's.id');
            })
            ->pluck('s.id');
        $this->setSellReason($exchangeSync, 'exchange_sync');
    }

    private function backfillClosedExecutions(): void
    {
        $ids = DB::table('bot_buy_executions as e')
            ->join('bot_orders as o', 'o.id', '=', 'e.bot_order_id')
            ->where('e.status', 'BOUGHT')
            ->where('o.status', 'CANCELED')
            ->whereExists(function ($q) {
                $q->selectRaw('1')
                    ->from('bot_sell_orders')
                    ->whereColumn('bot_sell_orders.bot_buy_execution_id', 'e.id')
                    ->where('bot_sell_orders.status', 'CANCELED');
            })
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('bot_sell_orders')
                    ->whereColumn('bot_sell_orders.bot_buy_execution_id', 'e.id')
                    ->where('bot_sell_orders.status', 'OPEN');
            })
            ->pluck('e.id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('bot_buy_executions')->whereIn('id', $ids)->update(['status' => 'CLOSED']);
    }

    private function setSellReason($ids, string $reason): void
    {
        if ($ids->isEmpty()) {
            return;
        }

        DB::table('bot_sell_orders')->whereIn('id', $ids)->update(['cancel_reason' => $reason]);
    }
};
