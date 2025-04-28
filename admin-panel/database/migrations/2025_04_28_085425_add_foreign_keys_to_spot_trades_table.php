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
        Schema::table('spot_trades', function (Blueprint $table) {
            // Assuming 'spot_orders' is the table name and 'id' is the primary key
            $table->foreign('maker_order_id')->references('id')->on('spot_orders')->onDelete('cascade');
            $table->foreign('taker_order_id')->references('id')->on('spot_orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spot_trades', function (Blueprint $table) {
            // Drop constraints in reverse order or by name
            // Laravel typically names constraints like: tablename_columnname_foreign
            $table->dropForeign(['maker_order_id']);
            $table->dropForeign(['taker_order_id']);
        });
    }
};
