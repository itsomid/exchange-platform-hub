<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bot_buy_executions', 'failure_reason')) {
            Schema::table('bot_buy_executions', function (Blueprint $table) {
                $table->string('failure_reason')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bot_buy_executions', 'failure_reason')) {
            Schema::table('bot_buy_executions', function (Blueprint $table) {
                $table->dropColumn('failure_reason');
            });
        }
    }
};
