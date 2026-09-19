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
        // The unique index only ever existed on the live MySQL database (it was
        // never part of create_exchanges_table), so on a fresh migrate — e.g.
        // sqlite :memory: in tests — there is nothing to drop.
        $indexExists = collect(Schema::getIndexes('exchanges'))
            ->pluck('name')
            ->contains('exchanges_is_active_unique');

        if (! $indexExists) {
            return;
        }

        Schema::table('exchanges', function (Blueprint $table) {
            $table->dropUnique('exchanges_is_active_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            $table->unique('is_active');
        });
    }
}; 