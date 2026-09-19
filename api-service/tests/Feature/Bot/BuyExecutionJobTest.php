<?php

use App\Jobs\Bot\BuyExecutionJob;
use App\Jobs\Bot\OpenSellOrdersJob;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotWallet;
use App\Models\Currency;
use App\Models\User;
use App\Services\Bot\ReferenceExchange\ExchangeContract;
use App\Services\Bot\ReferenceExchange\ExchangeOrderResult;
use App\Services\Bot\ReferenceExchange\ExchangeOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Support\FakeExchange;

uses(RefreshDatabase::class);

function makePendingExecution(): BotBuyExecution
{
    $user     = User::factory()->create();
    $currency = Currency::factory()->create(['symbol' => 'btc']);

    BotWallet::create([
        'user_id'        => $user->id,
        'balance'        => '0',
        'locked_balance' => '100',
    ]);

    $order = BotOrder::create([
        'user_id'           => $user->id,
        'batch_uuid'        => (string) \Illuminate\Support\Str::uuid(),
        'total_amount_usdt' => '100',
        'alpha_snapshot'    => 0.15,
        'status'            => 'COMPLETED',
        'triggered_by'      => 'MANUAL',
    ]);

    return BotBuyExecution::create([
        'bot_order_id'    => $order->id,
        'currency_id'     => $currency->id,
        'signal_snapshot' => [],
        'allocated_usdt'  => '100',
        'filled_amount'   => 0,
        'exchange_fee'    => 0,
        'network_fee'     => 0,
        'status'          => BotBuyExecution::STATUS_PENDING,
    ]);
}

it('on a successful market buy, marks execution BOUGHT and dispatches OpenSellOrdersJob', function () {
    Queue::fake();
    $execution = makePendingExecution();

    $fake = new FakeExchange();
    $fake->marketBuyFilledAmount = '0.001';
    $fake->marketBuyAvgPrice     = '100000';
    $fake->marketBuyExchangeFee  = '0.1';
    $fake->marketBuyFeeCurrency  = 'USDT';

    (new BuyExecutionJob($execution->id))->handle($fake);

    $execution->refresh();
    expect($execution->status)->toBe(BotBuyExecution::STATUS_BOUGHT);
    expect((string) $execution->filled_amount)->toEqual('0.00100000');
    expect((string) $execution->avg_buy_price)->toEqual('100000.00000000');
    expect((string) $execution->buy_ref_exchange_fee)->toEqual('0.10000000');
    expect($execution->buy_ref_exchange_fee_currency)->toBe('USDT');

    expect($fake->marketBuys)->toHaveCount(1);
    expect($fake->marketBuys[0]['market'])->toBe('BTCUSDT');
    expect($fake->marketBuys[0]['quoteAmount'])->toBe('100.00000000');

    Queue::assertPushed(OpenSellOrdersJob::class, fn ($job) => $job->executionId === $execution->id);
});

it('on a failed market buy, throws and never advances past BUYING', function () {
    $execution = makePendingExecution();

    $fake = new FakeExchange();
    $fake->failNextMarketBuy = true;

    expect(fn () => (new BuyExecutionJob($execution->id))->handle($fake))
        ->toThrow(RuntimeException::class);

    $execution->refresh();
    // After throw, status is BUYING; failed() hook (called by queue runtime) would reset it.
    expect($execution->status)->toBe(BotBuyExecution::STATUS_BUYING);
});

it('is idempotent on an execution that is no longer PENDING', function () {
    Queue::fake();
    $execution = makePendingExecution();
    $execution->update(['status' => BotBuyExecution::STATUS_BOUGHT]);

    $fake = new FakeExchange();
    (new BuyExecutionJob($execution->id))->handle($fake);

    expect($fake->marketBuys)->toBeEmpty();
    Queue::assertNothingPushed();
});
