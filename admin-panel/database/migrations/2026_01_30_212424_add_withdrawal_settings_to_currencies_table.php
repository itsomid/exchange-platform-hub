<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->boolean('ref_exchange_withdrawal_enabled')->default(true)->after('max_auto_withdraw_amount')->comment('Enable/disable withdrawal from ref exchange for this currency');
            $table->integer('ref_exchange_withdrawal_interval_minutes')->nullable()->after('ref_exchange_withdrawal_enabled')->comment('Time interval in minutes for withdrawal from ref exchange (null = use global setting)');
            $table->integer('ref_exchange_withdrawal_min_count')->nullable()->after('ref_exchange_withdrawal_interval_minutes')->comment('Minimum purchase count before withdrawal from ref exchange (null = use global setting)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn([
                'ref_exchange_withdrawal_enabled',
                'ref_exchange_withdrawal_interval_minutes',
                'ref_exchange_withdrawal_min_count',
            ]);
        });
    }
};
