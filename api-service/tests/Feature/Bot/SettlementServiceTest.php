<?php

use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotTradeSettlement;
use App\Models\Bot\BotWallet;
use App\Models\Currency;
use App\Models\User;
use App\Services\Bot\SettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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
