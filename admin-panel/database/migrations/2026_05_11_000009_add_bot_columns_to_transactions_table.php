<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('bot_order_id')->nullable()->after('stock_contract_id');
            $table->unsignedBigInteger('bot_buy_execution_id')->nullable()->after('bot_order_id');

            $table->foreign('bot_order_id')
                ->references('id')
                ->on('bot_orders')
                ->onDelete('set null');

            $table->foreign('bot_buy_execution_id')
                ->references('id')
                ->on('bot_buy_executions')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['bot_order_id']);
            $table->dropForeign(['bot_buy_execution_id']);
            $table->dropColumn(['bot_order_id', 'bot_buy_execution_id']);
        });
    }
};
