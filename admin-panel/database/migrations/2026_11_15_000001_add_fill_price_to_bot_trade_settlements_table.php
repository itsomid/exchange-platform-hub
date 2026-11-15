<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_trade_settlements', function (Blueprint $table) {
            $table->decimal('fill_price', 20, 8)->nullable()->after('gross_revenue')
                ->comment('Price multiplied by the settled amount to produce gross_revenue. Null on rows settled before this column existed.');
        });
    }

    public function down(): void
    {
        Schema::table('bot_trade_settlements', function (Blueprint $table) {
            $table->dropColumn('fill_price');
        });
    }
};
