<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Column was later back-filled into create_bot_buy_executions_table,
        // so a fresh migrate (e.g. sqlite :memory: in tests) already has it.
        if (Schema::hasColumn('bot_buy_executions', 'failure_reason')) {
            return;
        }

        Schema::table('bot_buy_executions', function (Blueprint $table) {
            $table->string('failure_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('bot_buy_executions', function (Blueprint $table) {
            $table->dropColumn('failure_reason');
        });
    }
};
