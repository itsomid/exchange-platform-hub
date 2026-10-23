<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tuning of bot:place-waiting-sells, editable on the bot settings page
 * (previously config/smart-bot.php `waiting` in api-service).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_global_settings', function (Blueprint $table) {
            $table->decimal('waiting_safety_margin_percent', 5, 2)->default(2)->after('waiting_sells_auto_place');
            $table->unsignedInteger('waiting_max_per_run')->default(200)->after('waiting_safety_margin_percent');
            $table->unsignedInteger('waiting_order_slot_reserve')->default(10)->after('waiting_max_per_run');
            $table->boolean('waiting_rotation_enabled')->default(true)->after('waiting_order_slot_reserve');
            $table->unsignedInteger('waiting_rotation_max_per_run')->default(30)->after('waiting_rotation_enabled');
            $table->decimal('waiting_rotation_min_gap_percent', 6, 2)->default(10)->after('waiting_rotation_max_per_run');
        });
    }

    public function down(): void
    {
        Schema::table('bot_global_settings', function (Blueprint $table) {
            $table->dropColumn([
                'waiting_safety_margin_percent',
                'waiting_max_per_run',
                'waiting_order_slot_reserve',
                'waiting_rotation_enabled',
                'waiting_rotation_max_per_run',
                'waiting_rotation_min_gap_percent',
            ]);
        });
    }
};
