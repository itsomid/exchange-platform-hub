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
        Schema::table('market_histories', function (Blueprint $table) {
            // decimal(18,8) only allows 10 integer digits; high-volume low-price
            // markets (e.g. 10012584509) exceed that limit.
            $table->decimal('volume', 30, 8)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('market_histories', function (Blueprint $table) {
            $table->decimal('volume', 18, 8)->change();
        });
    }
};
