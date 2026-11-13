<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The currencies page aggregates lifetime buys and every sell tier. These
 * covering indexes keep those scans off the execution rows, which store a
 * JSON snapshot. The single-column sell foreign key is a prefix of the new
 * sell index, so it is removed; otherwise MySQL can pick it and read the
 * full sell row for status and amount.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('bot_buy_executions', 'bot_buy_executions_currency_agg_idx')) {
            Schema::table('bot_buy_executions', function (Blueprint $table) {
                $table->index([
                    'status',
                    'currency_id',
                    'bot_order_id',
                    'allocated_usdt',
                    'filled_amount',
                    'avg_buy_price',
                    'buy_ref_exchange_fee',
                ], 'bot_buy_executions_currency_agg_idx');
            });
        }

        if (! Schema::hasIndex('bot_sell_orders', 'bot_sell_orders_currency_page_idx')) {
            Schema::table('bot_sell_orders', function (Blueprint $table) {
                $table->index([
                    'bot_buy_execution_id',
                    'status',
                    'amount_to_sell',
                ], 'bot_sell_orders_currency_page_idx');
            });
        }

        $this->dropSingleColumnIndex('bot_sell_orders', 'bot_buy_execution_id');
    }

    public function down(): void
    {
        if (! $this->hasSingleColumnIndex('bot_sell_orders', 'bot_buy_execution_id')) {
            Schema::table('bot_sell_orders', function (Blueprint $table) {
                $table->index('bot_buy_execution_id', 'bot_sell_orders_bot_buy_execution_id_foreign');
            });
        }

        if (Schema::hasIndex('bot_sell_orders', 'bot_sell_orders_currency_page_idx')) {
            Schema::table('bot_sell_orders', function (Blueprint $table) {
                $table->dropIndex('bot_sell_orders_currency_page_idx');
            });
        }

        if (Schema::hasIndex('bot_buy_executions', 'bot_buy_executions_currency_agg_idx')) {
            Schema::table('bot_buy_executions', function (Blueprint $table) {
                $table->dropIndex('bot_buy_executions_currency_agg_idx');
            });
        }
    }

    private function dropSingleColumnIndex(string $table, string $column): void
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (($index['columns'] ?? []) !== [$column] || ! empty($index['primary']) || ! empty($index['unique'])) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($index) {
                $blueprint->dropIndex($index['name']);
            });
        }
    }

    private function hasSingleColumnIndex(string $table, string $column): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (($index['columns'] ?? []) === [$column] && empty($index['primary']) && empty($index['unique'])) {
                return true;
            }
        }

        return false;
    }
};
