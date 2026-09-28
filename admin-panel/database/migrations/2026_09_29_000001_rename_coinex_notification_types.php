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
        DB::table('notifications')
            ->whereIn('type', [
                'App\Notifications\CoinexSpotTradingIsTooSmall',
                'App\Notifications\CoinexPriceDifferenceTooLarge',
                'App\Notifications\CoinexHasError',
            ])
            ->update(['type' => 'App\Notifications\RefExchangeOrderFailed']);

        DB::table('notifications')
            ->where('type', 'App\Notifications\CoinexWithdrawalProblem')
            ->update(['type' => 'App\Notifications\RefExchangeWithdrawalFailed']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The three order types were merged in up() and cannot be told apart again.
        DB::table('notifications')
            ->where('type', 'App\Notifications\RefExchangeOrderFailed')
            ->update(['type' => 'App\Notifications\CoinexHasError']);

        DB::table('notifications')
            ->where('type', 'App\Notifications\RefExchangeWithdrawalFailed')
            ->update(['type' => 'App\Notifications\CoinexWithdrawalProblem']);
    }
};
