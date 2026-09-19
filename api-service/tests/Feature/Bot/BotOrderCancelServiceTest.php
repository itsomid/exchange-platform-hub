<?php

use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotWallet;
use App\Models\Currency;
use App\Models\User;
use App\Services\Bot\BotOrderCancelService;
use App\Services\Bot\ReferenceExchange\ExchangeContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Support\FakeExchange;

uses(RefreshDatabase::class);

function makeCancelableBoughtPosition(): array
{
    $user     = User::factory()->create();
    $currency = Currency::forceCreate([
        'name'         => 'ETH',
        'persian_name' => 'ETH',
        'symbol'       => 'ETH',
    ]);

    BotWallet::create([
        'user_id'           => $user->id,
        'balance'           => '1000.00000000',
        'principal_balance' => '1000.00000000',
        'profit_balance'    => '0.00000000',
        'locked_balance'    => '1000.00000000',
    ]);

    BotGlobalSettings::create([
        'min_deposit_usdt'                  => 20,
        'alpha_weight'                      => 0.15,
        'default_sell_orders_count'         => 3,
        'performance_fee_percent'           => 22,
        'p2p_min_order_value'               => 5,
        'transfer_fee_tiers'                => [],
        'is_enabled'                        => true,
        'cancel_sell_on_exchange_enabled'   => false,
    ]);

    $order = BotOrder::create([
        'user_id'           => $user->id,
        'batch_uuid'        => (string) \Illuminate\Support\Str::uuid(),
        'total_amount_usdt' => '1000',
        'alpha_snapshot'    => 0.15,
        'status'            => 'PENDING',
        'triggered_by'      => 'MANUAL',
    ]);

    $execution = BotBuyExecution::create([
        'bot_order_id'    => $order->id,
        'currency_id'     => $currency->id,
        'signal_snapshot' => [],
        'allocated_usdt'  => '1000',
        'filled_amount'   => '10',
        'avg_buy_price'   => '100',
        'exchange_fee'    => 0,
        'network_fee'     => 0,
        'status'          => BotBuyExecution::STATUS_BOUGHT,
    ]);

    $sell = BotSellOrder::create([
        'bot_buy_execution_id' => $execution->id,
        'exchange_order_id'    => 'ex-99',
        'target_type'          => 'percent',
        'target_value'         => 20,
        'share_percent'        => 100,
        'amount_to_sell'       => '10',
        'status'               => BotSellOrder::STATUS_OPEN,
    ]);

    Cache::put('market:price:ETHUSDT', 100);

    return compact('order', 'execution', 'sell');
}

it('admin cancel stores source, sell reason, and closes the bought execution', function () {
    ['order' => $order, 'execution' => $execution, 'sell' => $sell] = makeCancelableBoughtPosition();

    $this->app->instance(ExchangeContract::class, new FakeExchange());

    app(BotOrderCancelService::class)->cancel($order, BotOrder::CANCEL_SOURCE_ADMIN);

    expect($order->fresh()->status)->toBe('CANCELED');
    expect($order->fresh()->cancel_source)->toBe(BotOrder::CANCEL_SOURCE_ADMIN);
    expect($sell->fresh()->status)->toBe(BotSellOrder::STATUS_CANCELED);
    expect($sell->fresh()->cancel_reason)->toBe(BotSellOrder::CANCEL_ADMIN);
    expect($execution->fresh()->status)->toBe(BotBuyExecution::STATUS_CLOSED);
});

it('user cancel stores user_cancel and closes the execution', function () {
    ['order' => $order, 'execution' => $execution, 'sell' => $sell] = makeCancelableBoughtPosition();

    $this->app->instance(ExchangeContract::class, new FakeExchange());

    app(BotOrderCancelService::class)->cancel($order, BotOrder::CANCEL_SOURCE_USER);

    expect($order->fresh()->cancel_source)->toBe(BotOrder::CANCEL_SOURCE_USER);
    expect($sell->fresh()->cancel_reason)->toBe(BotSellOrder::CANCEL_USER);
    expect($execution->fresh()->status)->toBe(BotBuyExecution::STATUS_CLOSED);
});

it('does not close a fully-filled execution that was not unwound', function () {
    ['order' => $order, 'execution' => $filledExec] = makeCancelableBoughtPosition();

    $filledExec->sellOrders()->update(['status' => BotSellOrder::STATUS_FILLED]);

    $openExec = BotBuyExecution::create([
        'bot_order_id'    => $order->id,
        'currency_id'     => $filledExec->currency_id,
        'signal_snapshot' => [],
        'allocated_usdt'  => '1000',
        'filled_amount'   => '10',
        'avg_buy_price'   => '100',
        'exchange_fee'    => 0,
        'network_fee'     => 0,
        'status'          => BotBuyExecution::STATUS_BOUGHT,
    ]);
    BotSellOrder::create([
        'bot_buy_execution_id' => $openExec->id,
        'exchange_order_id'    => 'ex-100',
        'target_type'          => 'percent',
        'target_value'         => 20,
        'share_percent'        => 100,
        'amount_to_sell'       => '10',
        'status'               => BotSellOrder::STATUS_OPEN,
    ]);

    $this->app->instance(ExchangeContract::class, new FakeExchange());
    app(BotOrderCancelService::class)->cancel($order, BotOrder::CANCEL_SOURCE_ADMIN);

    expect($filledExec->fresh()->status)->toBe(BotBuyExecution::STATUS_BOUGHT);
    expect($openExec->fresh()->status)->toBe(BotBuyExecution::STATUS_CLOSED);
});
