<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The auto-trade report filters settled_at, execution created_at, and
 * transfer-fee transactions by subtype plus created_at. These indexes let
 * the default one-week range read a slice instead of the whole table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('bot_buy_executions', 'bot_buy_executions_created_at_index')) {
            Schema::table('bot_buy_executions', function (Blueprint $table) {
                $table->index('created_at', 'bot_buy_executions_created_at_index');
            });
        }

        if (! Schema::hasIndex('bot_trade_settlements', 'bot_trade_settlements_settled_at_index')) {
            Schema::table('bot_trade_settlements', function (Blueprint $table) {
                $table->index('settled_at', 'bot_trade_settlements_settled_at_index');
            });
        }

        if (! Schema::hasIndex('transactions', 'transactions_subtype_created_at_index')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index(['subtype', 'created_at'], 'transactions_subtype_created_at_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('bot_buy_executions', function (Blueprint $table) {
            $table->dropIndex('bot_buy_executions_created_at_index');
        });

        Schema::table('bot_trade_settlements', function (Blueprint $table) {
            $table->dropIndex('bot_trade_settlements_settled_at_index');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_subtype_created_at_index');
        });
    }
};
