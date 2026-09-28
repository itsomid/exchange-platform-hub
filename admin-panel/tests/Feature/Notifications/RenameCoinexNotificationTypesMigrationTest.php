<?php

namespace Tests\Feature\Notifications;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RenameCoinexNotificationTypesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_coinex_types_are_moved_to_the_exchange_agnostic_ones(): void
    {
        foreach ([
            'App\Notifications\CoinexSpotTradingIsTooSmall',
            'App\Notifications\CoinexPriceDifferenceTooLarge',
            'App\Notifications\CoinexHasError',
            'App\Notifications\CoinexWithdrawalProblem',
            'App\Notifications\WithdrawalFailed',
        ] as $type) {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => $type,
                'notifiable_type' => 'App\Models\Admin',
                'notifiable_id' => 1,
                'data' => json_encode(['message' => 'x']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        (require database_path('migrations/2026_09_29_000001_rename_coinex_notification_types.php'))->up();

        $this->assertSame([
            'App\Notifications\RefExchangeOrderFailed' => 3,
            'App\Notifications\RefExchangeWithdrawalFailed' => 1,
            'App\Notifications\WithdrawalFailed' => 1,
        ], DB::table('notifications')->groupBy('type')->orderBy('type')->selectRaw('type, count(*) as total')
            ->pluck('total', 'type')->map(fn ($total) => (int) $total)->all());
    }
}
