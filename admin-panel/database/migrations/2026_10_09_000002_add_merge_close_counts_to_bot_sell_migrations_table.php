<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tiers below Binance's minimum order size are either merged into a sibling
 * tier of the same buy (merged_count) or, when that is not possible, sold in
 * one aggregated market order and settled back to the user (closed_count).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_sell_migrations', function (Blueprint $table) {
            $table->unsignedInteger('merged_count')->default(0)->after('completed_count');
            $table->unsignedInteger('closed_count')->default(0)->after('merged_count');
        });
    }

    public function down(): void
    {
        Schema::table('bot_sell_migrations', function (Blueprint $table) {
            $table->dropColumn(['merged_count', 'closed_count']);
        });
    }
};
