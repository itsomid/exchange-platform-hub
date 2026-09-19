<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('sweeper_tx_id')->nullable()->after('bot_wallet_transfer_id');

            $table->foreign('sweeper_tx_id')
                ->references('id')
                ->on('sweeper_transaction_logs')
                ->onDelete('set null');
        });

    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['sweeper_tx_id']);
            $table->dropColumn(['sweeper_tx_id']);
        });
    }
};
