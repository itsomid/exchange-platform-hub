<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::table('exchanges')->where('slug', 'binance')->exists()) {
            return;
        }

        DB::table('exchanges')->insert([
            'name' => 'Binance',
            'slug' => 'binance',
            'is_active' => false,
            'priority' => (int) DB::table('exchanges')->max('priority') + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('exchanges')
            ->where('slug', 'binance')
            ->where('is_active', false)
            ->delete();
    }
};
