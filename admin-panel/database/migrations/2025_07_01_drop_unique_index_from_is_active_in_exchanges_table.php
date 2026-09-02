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
        // On a fresh database the exchanges table is created without this unique
        // index, so only drop it when it actually exists.
        $indexExists = collect(DB::select("SHOW INDEX FROM exchanges"))
            ->contains(fn ($index) => $index->Key_name === 'exchanges_is_active_unique');

        if ($indexExists) {
            Schema::table('exchanges', function (Blueprint $table) {
                $table->dropUnique('exchanges_is_active_unique');
            });
        }
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