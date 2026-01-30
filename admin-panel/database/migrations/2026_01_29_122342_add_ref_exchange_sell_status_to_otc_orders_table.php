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
        Schema::table('otc_orders', function (Blueprint $table) {
            $table->string('ref_exchange_sell_status')->default('not_required')->after('ref_exchange_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otc_orders', function (Blueprint $table) {
            $table->dropColumn('ref_exchange_sell_status');
        });
    }
};
