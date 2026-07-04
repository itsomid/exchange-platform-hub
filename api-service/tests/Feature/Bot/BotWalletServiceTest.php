<?php

use App\Exceptions\Bot\BotTransferAmountTooLowException;
use App\Exceptions\Bot\InsufficientBotWalletException;
use App\Models\Bot\BotWallet;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Bot\BotWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createUserWithUsdtWallet(string $balance = '1000.00000000'): array
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $wallet = Wallet::create([
        'user_id'         => $user->id,
        'currency_symbol' => 'USDT',
        'balance'         => $balance,
        'locked_balance'  => '0.00000000',
    ]);

    return [$user, $wallet];
}

// ─── transferIn ─────────────────────────────────────────────────────────────

it('rejects transfer in below minimum (< 20 USDT)', function () {
    [$user] = createUserWithUsdtWallet();
    $service = app(BotWalletService::class);

    expect(fn () => $service->transferIn($user, '19'))->toThrow(BotTransferAmountTooLowException::class);
});

it('rejects transfer in when user has insufficient main balance', function () {
    [$user] = createUserWithUsdtWallet('10.00000000');
    $service = app(BotWalletService::class);

    expect(fn () => $service->transferIn($user, '50'))->toThrow(InsufficientBotWalletException::class);
});

it('creates bot wallet and deducts from main wallet on transfer in', function () {
    [$user, $mainWallet] = createUserWithUsdtWallet('500.00000000');
    $service = app(BotWalletService::class);

    $service->transferIn($user, '100');

    $mainWallet->refresh();
    expect($mainWallet->balance)->toBe('400.00000000');

    $botWallet = BotWallet::where('user_id', $user->id)->first();
    expect($botWallet)->not->toBeNull();
    expect($botWallet->balance)->toBe('99.00000000');
    // principal_balance is a D1 placeholder, not touched by transfer flows
    expect($botWallet->principal_balance)->toBe('0.00000000');
});

it('records two transactions on transfer in (transfer + fee)', function () {
    [$user] = createUserWithUsdtWallet('500.00000000');
    $service = app(BotWalletService::class);

    $service->transferIn($user, '200');

    $botWallet = BotWallet::where('user_id', $user->id)->first();
    expect($botWallet->balance)->toBe('198.00000000');

    $txns = Transaction::where('user_id', $user->id)->get();
    expect($txns)->toHaveCount(2);

    $subtypes = $txns->pluck('subtype')->map->value->toArray();
    expect($subtypes)->toContain('bot_transfer_out');
    expect($subtypes)->toContain('bot_transfer_fee');
});

// ─── transferOut ────────────────────────────────────────────────────────────

it('rejects transfer out when bot balance is insufficient', function () {
    [$user] = createUserWithUsdtWallet('500.00000000');

    BotWallet::create([
        'user_id'           => $user->id,
        'balance'           => '20.00000000',
        'principal_balance' => '19.00000000',
        'profit_balance'    => '0.00000000',
        'locked_balance'    => '0.00000000',
    ]);

    $service = app(BotWalletService::class);

    // 20 USDT needs fee=1, total=21 — but free_balance is 20
    expect(fn () => $service->transferOut($user, '20'))->toThrow(InsufficientBotWalletException::class);
});

it('respects locked_balance when checking available bot balance (double-spend protection)', function () {
    [$user] = createUserWithUsdtWallet('100.00000000');

    BotWallet::create([
        'user_id'           => $user->id,
        'balance'           => '100.00000000',
        'principal_balance' => '100.00000000',
        'profit_balance'    => '0.00000000',
        'locked_balance'    => '80.00000000', // 80 locked
    ]);

    $service = app(BotWalletService::class);

    // free_balance = 100 - 80 = 20; withdraw 20 needs fee 1, total 21 > 20 → fail
    expect(fn () => $service->transferOut($user, '20'))->toThrow(InsufficientBotWalletException::class);
});

it('transfers out successfully and credits main wallet', function () {
    [$user, $mainWallet] = createUserWithUsdtWallet('100.00000000');

    BotWallet::create([
        'user_id'           => $user->id,
        'balance'           => '200.00000000',
        'principal_balance' => '200.00000000',
        'profit_balance'    => '0.00000000',
        'locked_balance'    => '0.00000000',
    ]);

    $service = app(BotWalletService::class);
    $service->transferOut($user, '100');

    $mainWallet->refresh();
    // Main wallet received 100
    expect($mainWallet->balance)->toBe('200.00000000');

    $botWallet = BotWallet::where('user_id', $user->id)->first();
    // fee for 100 = 1, total deducted = 101
    expect($botWallet->balance)->toBe('99.00000000');
});
