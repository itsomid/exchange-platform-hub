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
        DB::table('transactions')->where('subtype', 'coinex')->update(['subtype' => 'ref_exchange']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('transactions')->where('subtype', 'ref_exchange')->update(['subtype' => 'coinex']);
    }
};
