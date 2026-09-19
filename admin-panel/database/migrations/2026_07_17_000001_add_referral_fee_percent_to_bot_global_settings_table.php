<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_global_settings', function (Blueprint $table) {
            $table->decimal('referral_fee_percent', 5, 2)->default(4)->after('performance_fee_percent')
                ->comment('Percent of profit paid to the introducer, deducted from the performance fee share (e.g. perf 22% = exchange 18% + referral 4%).');
        });
    }

    public function down(): void
    {
        Schema::table('bot_global_settings', function (Blueprint $table) {
            $table->dropColumn('referral_fee_percent');
        });
    }
};
