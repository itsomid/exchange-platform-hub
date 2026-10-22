<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sell tiers Binance refuses for now (price above its PERCENT_PRICE_BY_SIDE
 * ceiling) stay OPEN with placement_status = WAITING and no exchange order;
 * bot:place-waiting-sells (or an admin) places them once the price is in range.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_sell_orders', function (Blueprint $table) {
            $table->string('placement_status', 16)->default('PLACED')->after('status')->index();
            $table->timestamp('waiting_since')->nullable()->after('placement_status');
            $table->unsignedInteger('placement_attempts')->default(0)->after('waiting_since');
            $table->string('last_placement_error', 255)->nullable()->after('placement_attempts');
            $table->timestamp('last_placement_check_at')->nullable()->after('last_placement_error');
        });

        Schema::table('bot_global_settings', function (Blueprint $table) {
            $table->boolean('waiting_sells_auto_place')->default(true)->after('cancel_sell_on_exchange_enabled');
        });

        Schema::table('bot_sell_migrations', function (Blueprint $table) {
            $table->unsignedInteger('waiting_count')->default(0)->after('closed_count');
        });
    }

    public function down(): void
    {
        Schema::table('bot_sell_migrations', function (Blueprint $table) {
            $table->dropColumn('waiting_count');
        });

        Schema::table('bot_global_settings', function (Blueprint $table) {
            $table->dropColumn('waiting_sells_auto_place');
        });

        Schema::table('bot_sell_orders', function (Blueprint $table) {
            $table->dropIndex(['placement_status']);
            $table->dropColumn(['placement_status', 'waiting_since', 'placement_attempts', 'last_placement_error', 'last_placement_check_at']);
        });
    }
};
