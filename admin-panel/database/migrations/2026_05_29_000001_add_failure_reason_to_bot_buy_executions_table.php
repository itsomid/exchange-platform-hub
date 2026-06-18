<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_buy_executions', function (Blueprint $table) {
            $table->unsignedTinyInteger('effective_sell_orders_count')
                ->nullable()
                ->after('original_sell_orders_count')
                ->comment('Number of sell targets after smart-collapse (may be < original)');

            $table->text('failure_reason')
                ->nullable()
                ->after('status')
                ->comment('Reason when status is FAILED or SKIPPED, or smart-collapse log');
        });
    }

    public function down(): void
    {
        Schema::table('bot_buy_executions', function (Blueprint $table) {
            $table->dropColumn(['effective_sell_orders_count', 'failure_reason']);
        });
    }
};
