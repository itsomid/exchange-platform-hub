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
      

        // Add the foreign key constraint if it doesn't exist
        Schema::table('currency_chains', function (Blueprint $table) {
            // Check if the column exists

            // Add the foreign key constraint without trying to drop it first
            $table->foreign('currency_id')
                  ->references('id')
                  ->on('currencies')
                  ->onDelete('cascade');
        });
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
