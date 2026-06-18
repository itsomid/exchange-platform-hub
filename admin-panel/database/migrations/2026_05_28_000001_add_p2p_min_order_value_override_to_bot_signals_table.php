<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_signals', function (Blueprint $table) {
            $table->decimal('p2p_min_order_value_override', 18, 8)->nullable()->after('min_buy_amount_usdt');
        });
    }

    public function down(): void
    {
        Schema::table('bot_signals', function (Blueprint $table) {
            $table->dropColumn('p2p_min_order_value_override');
        });
    }
};
