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
        Schema::table('otc_ref_exchange_withdrawals', function (Blueprint $table) {
            // First drop the existing foreign key so it can be recreated with the
            // desired cascade behavior (the table's create migration already adds one).
            $table->dropForeign(['currency_id']);

            // Then add the new foreign key with your desired constraints
            $table->foreign('currency_id')
                ->references('id')
                ->on('currencies')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otc_ref_exchange_withdrawals', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            
            // If you want to restore the original constraint in the down method
            // $table->foreign('currency_id')->references('id')->on('currencies');
        });
    }
};