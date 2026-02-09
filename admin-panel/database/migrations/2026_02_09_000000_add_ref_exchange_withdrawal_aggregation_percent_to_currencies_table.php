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
            $table->unsignedTinyInteger('ref_exchange_withdrawal_aggregation_percent')
                ->nullable()
                ->after('ref_exchange_withdrawal_min_count')
                ->comment('Default aggregation percentage for withdrawal from ref exchange (null = 100% full amount)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn('ref_exchange_withdrawal_aggregation_percent');
        });
    }
};
