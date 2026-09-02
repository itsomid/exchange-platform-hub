<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // On a fresh database the currency_chains create migration already adds
        // this foreign key, so only add it when it is genuinely missing.
        $foreignKeyExists = ! empty(DB::select(
            "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = 'currency_chains'
               AND CONSTRAINT_NAME = 'currency_chains_currency_id_foreign'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        ));

        if (! $foreignKeyExists) {
            Schema::table('currency_chains', function (Blueprint $table) {
                $table->foreign('currency_id')
                      ->references('id')
                      ->on('currencies')
                      ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    
        // If the foreign key exists, drop it
      
            Schema::table('currency_chains', function (Blueprint $table) {
                $table->dropForeign(['currency_id']);
            });
        
    }
};
