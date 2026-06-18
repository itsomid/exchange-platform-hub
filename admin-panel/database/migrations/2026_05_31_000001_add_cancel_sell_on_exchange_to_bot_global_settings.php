<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_global_settings', function (Blueprint $table) {
            $table->boolean('cancel_sell_on_exchange_enabled')
                ->default(true)
                ->after('is_enabled')
                ->comment('If true, cancelling a bot order market-sells the held coins on the reference exchange. If false, only fake settlement transactions are written.');
        });
    }

    public function down(): void
    {
        Schema::table('bot_global_settings', function (Blueprint $table) {
            $table->dropColumn('cancel_sell_on_exchange_enabled');
        });
    }
};
