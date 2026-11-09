<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Report aggregates only need a handful of execution columns. A covering index
 * keeps that range scan off the clustered rows, which store a JSON snapshot
 * on every execution. The plain created_at index is a prefix of this one and
 * would make MySQL read those JSON rows anyway, so it is replaced.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('bot_buy_executions', 'bot_buy_executions_created_at_index')) {
            Schema::table('bot_buy_executions', function (Blueprint $table) {
                $table->dropIndex('bot_buy_executions_created_at_index');
            });
        }

        if (! Schema::hasIndex('bot_buy_executions', 'bot_buy_executions_report_idx')) {
            Schema::table('bot_buy_executions', function (Blueprint $table) {
                $table->index([
                    'created_at',
                    'currency_id',
                    'status',
                    'bot_order_id',
                    'allocated_usdt',
                    'filled_amount',
                    'original_sell_orders_count',
                    'effective_sell_orders_count',
                ], 'bot_buy_executions_report_idx');
            });
        }

        if (! Schema::hasIndex('bot_wallet_transfers', 'bot_wallet_transfers_created_at_index')) {
            Schema::table('bot_wallet_transfers', function (Blueprint $table) {
                $table->index('created_at', 'bot_wallet_transfers_created_at_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('bot_buy_executions', function (Blueprint $table) {
            $table->dropIndex('bot_buy_executions_report_idx');
        });

        if (! Schema::hasIndex('bot_buy_executions', 'bot_buy_executions_created_at_index')) {
            Schema::table('bot_buy_executions', function (Blueprint $table) {
                $table->index('created_at', 'bot_buy_executions_created_at_index');
            });
        }

        Schema::table('bot_wallet_transfers', function (Blueprint $table) {
            $table->dropIndex('bot_wallet_transfers_created_at_index');
        });
    }
};
