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
        Schema::table('trading_commissions', function (Blueprint $table) {
            $table->string('maker_commission_currency', 10)->nullable()->after('maker_commission_percentage');
            $table->string('taker_commission_currency', 10)->nullable()->after('taker_commission_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trading_commissions', function (Blueprint $table) {
            $table->dropColumn('taker_commission_currency');
            $table->dropColumn('maker_commission_currency');

        });
    }
};
