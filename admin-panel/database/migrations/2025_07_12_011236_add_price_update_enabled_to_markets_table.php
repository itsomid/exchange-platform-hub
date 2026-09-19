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
        // Column was later back-filled into create_markets_table, so a fresh
        // migrate (e.g. sqlite :memory: in tests) already has it.
        if (Schema::hasColumn('markets', 'price_update_enabled')) {
            return;
        }

        Schema::table('markets', function (Blueprint $table) {
            $table->boolean('price_update_enabled')->default(false)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('markets', function (Blueprint $table) {
            $table->dropColumn('price_update_enabled');
        });
    }
};
