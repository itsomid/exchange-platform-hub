<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('bot_wallet_transfer_id')->nullable()->after('bot_buy_execution_id');

            $table->foreign('bot_wallet_transfer_id')
                ->references('id')
                ->on('bot_wallet_transfers')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['bot_wallet_transfer_id']);
            $table->dropColumn(['bot_wallet_transfer_id']);
        });
    }
};
