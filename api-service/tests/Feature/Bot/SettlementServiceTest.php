<?php

use App\Enums\TransactionSubTypeEnum;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotTradeSettlement;
use App\Models\Bot\BotWallet;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Bot\SettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function ensureExchangeUsdtWallet(string $balance = '0.00000000'): Wallet
{
    $exchangeUser = User::factory()->create();
    config(['bitexroom.user_id' => $exchangeUser->id]);

    return Wallet::create([
        'user_id'         => $exchangeUser->id,
        'currency_symbol' => 'USDT',
        'balance'         => $balance,
        'locked_balance'  => '0.00000000',
    ]);
}

function makeOpenSellOrder(string $filledAmount, string $avgBuyPrice, string $startingBalance, string $startingLocked, string $startingProfit = '0'): BotSellOrder
{
    $user     = User::factory()->create();
    $currency = Currency::factory()->create();

    BotWallet::create([
        'user_id'           => $user->id,
        'balance'           => $startingBalance,
        'principal_balance' => $startingBalance,
        'profit_balance'    => $startingProfit,
        'locked_balance'    => $startingLocked,
    ]);

    $order = BotOrder::create([
        'user_id'           => $user->id,
        'batch_uuid'        => (string) \Illuminate\Support\Str::uuid(),
        'total_amount_usdt' => $filledAmount,
        'alpha_snapshot'    => 0.15,
        'status'            => 'COMPLETED',
        'triggered_by'      => 'MANUAL',
    ]);
    $execution = BotBuyExecution::create([
        'bot_order_id'    => $order->id,
        'currency_id'     => $currency->id,
        'signal_snapshot' => [],
        'allocated_usdt'  => $filledAmount,
        'filled_amount'   => $filledAmount,
        'avg_buy_price'   => $avgBuyPrice,
        'exchange_fee'    => 0,
        'network_fee'     => 0,
        'status'          => BotBuyExecution::STATUS_BOUGHT,
    ]);
    return BotSellOrder::create([
        'bot_buy_execution_id' => $execution->id,
        'p2p_order_id'         => 12345,
        'target_type'          => 'percent',
        'target_value'         => 20,
        'share_percent'        => 100,
        'amount_to_sell'       => $filledAmount,
        'status'               => BotSellOrder::STATUS_OPEN,
    ]);
}

it('settles a profitable fill — performance fee applied, profit_balance increases', function () {
    // bought 10 units @ 100 → cost basis 1000; sold @ 120 → revenue 1200; no fees
    // gross_pnl = 200; perf fee 22% = 44; net = 156
    $exchangeWallet = ensureExchangeUsdtWallet();
    $sell = makeOpenSellOrder('10', '100', '0', '1000');

    /** @var SettlementService $svc */
    $svc        = app(SettlementService::class);
    $settlement = $svc->settleFill($sell, '10', '120');

    expect((float) $settlement->gross_revenue)->toBe(1200.0);
    expect((float) $settlement->cost_basis)->toBe(1000.0);
    expect((float) $settlement->performance_fee)->toBe(44.0);
    expect((float) $settlement->net_pnl)->toBe(156.0);

    $wallet = BotWallet::where('user_id', $settlement->user_id)->first();
    expect((float) $wallet->balance)->toBe(1156.0);
    expect((float) $wallet->profit_balance)->toBe(156.0);
    expect((float) $wallet->locked_balance)->toBe(0.0);

    expect($sell->fresh()->status)->toBe(BotSellOrder::STATUS_FILLED);

    $exchangeWallet->refresh();
    expect((float) $exchangeWallet->balance)->toBe(44.0);

    $perfTxn = Transaction::where('subtype', TransactionSubTypeEnum::BOT_PERFORMANCE_FEE)->first();
    expect($perfTxn)->not->toBeNull();
    expect($perfTxn->user_id)->toBe((int) config('bitexroom.user_id'));
    expect($perfTxn->wallet_id)->toBe($exchangeWallet->id);
    expect((float) $perfTxn->amount)->toBe(44.0);

    expect(Transaction::where('subtype', TransactionSubTypeEnum::BOT_SELL)->count())->toBe(0);
});

it('records BOT_EXCHANGE_FEE on the exchange fee-currency wallet, not the trader', function () {
    $exchangeUser = User::factory()->create();
    config(['bitexroom.user_id' => $exchangeUser->id]);
    Wallet::create([
        'user_id'         => $exchangeUser->id,
        'currency_symbol' => 'USDT',
        'balance'         => '0.00000000',
        'locked_balance'  => '0.00000000',
    ]);

    $sell = makeOpenSellOrder('10', '100', '0', '1000');
    $execution = $sell->botBuyExecution;
    $execution->update([
        'buy_ref_exchange_fee'          => '0',
        'buy_ref_exchange_fee_currency' => 'USDT',
    ]);

    // sell ref fee 5 USDT; no buy fee share → exchange_fee = 5
    app(SettlementService::class)->settleFill($sell, '10', '120', '0', '5');

    $feeTxn = Transaction::where('subtype', TransactionSubTypeEnum::BOT_EXCHANGE_FEE)->first();
    expect($feeTxn)->not->toBeNull();
    expect($feeTxn->user_id)->toBe($exchangeUser->id);
    expect((float) $feeTxn->amount)->toBe(-5.0);
    expect($feeTxn->description)->toContain((string) $execution->bot_order_id);
    expect($feeTxn->description)->toContain('صرافی مرجع');

    $feeWallet = Wallet::find($feeTxn->wallet_id);
    expect($feeWallet->user_id)->toBe($exchangeUser->id);
    expect($feeWallet->currency_symbol)->toBe('USDT');

    expect(
        Transaction::where('user_id', $execution->botOrder->user_id)
            ->where('subtype', TransactionSubTypeEnum::BOT_EXCHANGE_FEE)
            ->count()
    )->toBe(0);
});

it('settles a losing fill — no performance fee, profit_balance untouched', function () {
    // bought 10 @ 100 → cost 1000; sold 10 @ 90 → revenue 900; gross_pnl = -100; net = -100
    $sell = makeOpenSellOrder('10', '100', '0', '1000');

    $settlement = app(SettlementService::class)->settleFill($sell, '10', '90');

    expect((float) $settlement->performance_fee)->toBe(0.0);
    expect((float) $settlement->net_pnl)->toBe(-100.0);

    $wallet = BotWallet::where('user_id', $settlement->user_id)->first();
    expect((float) $wallet->balance)->toBe(900.0);
    expect((float) $wallet->profit_balance)->toBe(0.0);
});

it('settles a cancel with cancel_fee and returns principal minus fee', function () {
    // unsold 10 @ avg 100 → cost_basis 1000; cancel_fee=12 (since > 1000? equals 1000 → 1%? we pass explicitly)
    $sell = makeOpenSellOrder('10', '100', '0', '1000');

    $settlement = app(SettlementService::class)->settleCancel($sell, '10');

    expect((float) $settlement->cancel_fee)->toBe(10.0);
    expect((float) $settlement->net_pnl)->toBe(-10.0);

    $wallet = BotWallet::where('user_id', $settlement->user_id)->first();
    // balance += cost_basis + net_pnl = 1000 + (-10) = 990
    expect((float) $wallet->balance)->toBe(990.0);
    expect((float) $wallet->locked_balance)->toBe(0.0);
    expect($sell->fresh()->status)->toBe(BotSellOrder::STATUS_CANCELED);
});
