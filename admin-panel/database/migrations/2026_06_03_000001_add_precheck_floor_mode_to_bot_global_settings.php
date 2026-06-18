<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_global_settings', function (Blueprint $table) {
            $table->string('precheck_floor_mode', 10)->default('multi')->after('p2p_min_order_value');
        });
    }

    public function down(): void
    {
        Schema::table('bot_global_settings', function (Blueprint $table) {
            $table->dropColumn('precheck_floor_mode');
        });
    }
};
