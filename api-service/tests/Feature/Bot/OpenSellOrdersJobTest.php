<?php

use App\Jobs\Bot\OpenSellOrdersJob;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotSignal;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeExchange;

uses(RefreshDatabase::class);

function makeExecutionWithSignal(string $filledAmount, array $sellTargets, string $p2pMin): BotBuyExecution
{
    $user     = User::factory()->create();
    $currency = Currency::factory()->create();

    BotSignal::create([
        'currency_id'              => $currency->id,
        'is_active'                => true,
        'priority'                 => 1,
        'min_buy_amount_usdt'      => 5,
        'max_buy_amount_usdt'      => 1000,
        'floor_price'              => 1,
        'ceiling_price'            => 1000,
        'buy_price'                => 100,
        'sell_orders_count'        => count($sellTargets),
        'sell_mode'                => 'percent',
        'sell_targets'             => $sellTargets,
        'p2p_min_order_value_override' => $p2pMin,
    ]);

    $order = BotOrder::create([
        'user_id'           => $user->id,
        'batch_uuid'        => (string) \Illuminate\Support\Str::uuid(),
        'total_amount_usdt' => $filledAmount,
        'alpha_snapshot'    => 0.15,
        'status'            => 'COMPLETED',
        'triggered_by'      => 'MANUAL',
    ]);

    return BotBuyExecution::create([
        'bot_order_id'    => $order->id,
        'currency_id'     => $currency->id,
        'signal_snapshot' => ['p2p_min_order_value' => $p2pMin],
        'allocated_usdt'  => $filledAmount,
        'filled_amount'   => $filledAmount,
        'avg_buy_price'   => 100,
        'exchange_fee'    => 0,
        'network_fee'     => 0,
        'status'          => BotBuyExecution::STATUS_BOUGHT,
    ]);
}

it('opens 2 sell orders when filled=10 collapses 4→2', function () {
    $execution = makeExecutionWithSignal('10', [
        ['trigger' => 20, 'share' => 25],
        ['trigger' => 30, 'share' => 25],
        ['trigger' => 40, 'share' => 25],
        ['trigger' => 50, 'share' => 25],
    ], '5');

    $fake = new FakeExchange();
    (new OpenSellOrdersJob($execution->id))->handle(new \App\Services\Bot\TargetCollapseService(), $fake);

    $execution->refresh();
    expect($execution->original_sell_orders_count)->toBe(4);
    expect($execution->effective_sell_orders_count)->toBe(2);

    $orders = BotSellOrder::where('bot_buy_execution_id', $execution->id)->get();
    expect($orders)->toHaveCount(2);
    foreach ($orders as $o) {
        expect((float) $o->amount_to_sell)->toBe(5.0);
        expect((float) $o->share_percent)->toBe(50.0);
        expect($o->status)->toBe(BotSellOrder::STATUS_OPEN);
        expect($o->exchange_order_id)->not->toBeNull();
    }
    expect($fake->limitSells)->toHaveCount(2);
});

it('opens 1 sell order when filled=5 collapses 4→1', function () {
    $execution = makeExecutionWithSignal('5', [
        ['trigger' => 20, 'share' => 25],
        ['trigger' => 30, 'share' => 25],
        ['trigger' => 40, 'share' => 25],
        ['trigger' => 50, 'share' => 25],
    ], '5');

    (new OpenSellOrdersJob($execution->id))->handle(new \App\Services\Bot\TargetCollapseService(), new FakeExchange());

    $execution->refresh();
    expect($execution->effective_sell_orders_count)->toBe(1);

    $orders = BotSellOrder::where('bot_buy_execution_id', $execution->id)->get();
    expect($orders)->toHaveCount(1);
    expect((float) $orders->first()->amount_to_sell)->toBe(5.0);
    expect((float) $orders->first()->share_percent)->toBe(100.0);
});

it('opens 4 sell orders unchanged when filled=20 covers all minimums', function () {
    $execution = makeExecutionWithSignal('20', [
        ['trigger' => 20, 'share' => 25],
        ['trigger' => 30, 'share' => 25],
        ['trigger' => 40, 'share' => 25],
        ['trigger' => 50, 'share' => 25],
    ], '5');

    (new OpenSellOrdersJob($execution->id))->handle(new \App\Services\Bot\TargetCollapseService(), new FakeExchange());

    $execution->refresh();
    expect($execution->original_sell_orders_count)->toBe(4);
    expect($execution->effective_sell_orders_count)->toBe(4);
    expect($execution->failure_reason)->toBeNull();

    $orders = BotSellOrder::where('bot_buy_execution_id', $execution->id)->orderBy('id')->get();
    expect($orders)->toHaveCount(4);
    foreach ($orders as $o) {
        expect((float) $o->amount_to_sell)->toBe(5.0);
    }
});
