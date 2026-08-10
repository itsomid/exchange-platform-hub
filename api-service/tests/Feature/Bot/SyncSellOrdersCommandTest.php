<?php

use App\Console\Commands\Bot\SyncSellOrdersCommand;
use App\Events\Bot\BotSellOrderFilled;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Currency;
use App\Models\User;
use App\Services\Bot\ReferenceExchange\ExchangeContract;
use App\Services\Bot\ReferenceExchange\ExchangeOrderResult;
use App\Services\Bot\ReferenceExchange\ExchangeOrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Support\FakeExchange;

uses(RefreshDatabase::class);

function makeOpenSellOrder(string $exchangeOrderId, string $symbol = 'eth'): BotSellOrder
{
    $user     = User::factory()->create();
    $currency = Currency::forceCreate([
        'name'         => strtoupper($symbol),
        'persian_name' => $symbol,
        'symbol'       => $symbol,
    ]);

    $order = BotOrder::create([
        'user_id'           => $user->id,
        'batch_uuid'        => (string) \Illuminate\Support\Str::uuid(),
        'total_amount_usdt' => '100',
        'alpha_snapshot'    => 0.15,
        'status'            => 'COMPLETED',
        'triggered_by'      => 'MANUAL',
    ]);

    $execution = BotBuyExecution::create([
        'bot_order_id'    => $order->id,
        'currency_id'     => $currency->id,
        'signal_snapshot' => [],
        'allocated_usdt'  => '100',
        'filled_amount'   => '0.05',
        'avg_buy_price'   => '2000',
        'exchange_fee'    => 0,
        'network_fee'     => 0,
        'status'          => BotBuyExecution::STATUS_BOUGHT,
    ]);

    return BotSellOrder::create([
        'bot_buy_execution_id' => $execution->id,
        'exchange_order_id'    => $exchangeOrderId,
        'target_type'          => 'percent',
        'target_value'         => '5',
        'share_percent'        => '100',
        'amount_to_sell'       => '0.05',
        'status'               => BotSellOrder::STATUS_OPEN,
    ]);
}

it('dispatches BotSellOrderFilled when CoinEx reports the order as FILLED', function () {
    Event::fake([BotSellOrderFilled::class]);
    $sellOrder = makeOpenSellOrder('99999');

    $fake = new FakeExchange();
    $fake->setStatus('99999', new ExchangeOrderResult(
        exchangeOrderId: '99999',
        status:          ExchangeOrderStatus::FILLED,
        filledAmount:    '0.05',
        avgPrice:        '2100',
        exchangeFee:     '0.105',
    ));

    $this->app->instance(ExchangeContract::class, $fake);

    $exit = $this->artisan('bot:sync-sell-orders')->run();

    expect($exit)->toBe(0);
    expect($fake->statusQueries)->toHaveCount(1);
    expect($fake->statusQueries[0]['market'])->toBe('ETHUSDT');

    Event::assertDispatched(BotSellOrderFilled::class, function (BotSellOrderFilled $event) use ($sellOrder) {
        return $event->sellOrder->id === $sellOrder->id
            && $event->fillPrice === '2100'
            && $event->exchangeFee === '0.105';
    });
});

it('flips local row to CANCELED when CoinEx reports CANCELED', function () {
    $sellOrder = makeOpenSellOrder('77777');

    $fake = new FakeExchange();
    $fake->setStatus('77777', new ExchangeOrderResult(
        exchangeOrderId: '77777',
        status:          ExchangeOrderStatus::CANCELED,
    ));

    $this->app->instance(ExchangeContract::class, $fake);

    $this->artisan('bot:sync-sell-orders')->assertExitCode(0);

    $sellOrder->refresh();
    expect($sellOrder->status)->toBe(BotSellOrder::STATUS_CANCELED);
});

it('rotates through the OPEN backlog across runs instead of re-polling the same head', function () {
    Event::fake([BotSellOrderFilled::class]);

    makeOpenSellOrder('1001', 'eth');
    makeOpenSellOrder('1002', 'btc');
    makeOpenSellOrder('1003', 'sol');

    $fake = new FakeExchange(); // every getOrder returns OPEN by default
    $this->app->instance(ExchangeContract::class, $fake);

    // First run: polls the first two orders.
    $this->artisan('bot:sync-sell-orders', ['--limit' => 2])->assertExitCode(0);
    expect(array_column($fake->statusQueries, 'exchangeOrderId'))->toBe(['1001', '1002']);

    // Second run: continues with the third order, then wraps to the first.
    $this->artisan('bot:sync-sell-orders', ['--limit' => 2])->assertExitCode(0);
    expect(array_column($fake->statusQueries, 'exchangeOrderId'))->toBe(['1001', '1002', '1003', '1001']);
});

it('skips orders without exchange_order_id', function () {
    Event::fake([BotSellOrderFilled::class]);

    $sellOrder = makeOpenSellOrder('11111');
    $sellOrder->update(['exchange_order_id' => null]);

    $fake = new FakeExchange();
    $this->app->instance(ExchangeContract::class, $fake);

    $this->artisan('bot:sync-sell-orders')->assertExitCode(0);

    expect($fake->statusQueries)->toBeEmpty();
    Event::assertNotDispatched(BotSellOrderFilled::class);
});
