<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fortify's `confirm` option (2FA is only active after the first valid code is entered)
 * treats a secret without `two_factor_confirmed_at` as "not enabled". Admins who enabled
 * 2FA before that option existed must stay protected, so mark their secrets as confirmed.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('admins')
            ->whereNotNull('two_factor_secret')
            ->whereNull('two_factor_confirmed_at')
            ->update(['two_factor_confirmed_at' => now()]);
    }

    public function down(): void
    {
        // Irreversible data fix: we can't know which rows were backfilled.
    }
};
