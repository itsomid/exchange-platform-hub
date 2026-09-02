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
        // Skip when the column already exists on a fresh database.
        if (Schema::hasColumn('transactions', 'stock_contract_id')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('stock_contract_id')->nullable()->after('spot_trade_id');
            $table->foreign('stock_contract_id')
                ->references('id')->on('stock_contracts')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['stock_contract_id']);
            $table->dropColumn('stock_contract_id');
        });
    }
};
