<?php

use App\Events\Bot\BotSellOrderFilled;
use App\Jobs\Bot\OpenSellOrdersJob;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotCurrencyCancellation;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotSignal;
use App\Models\Currency;
use App\Models\User;
use App\Services\Bot\ReferenceExchange\ExchangeContract;
use App\Services\Bot\TargetCollapseService;
use App\Services\Bot\WaitingSellTiersService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakeExchange;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->fake = new FakeExchange();
    $this->fake->exchangeName = BotSellOrder::EXCHANGE_BINANCE;
    $this->app->instance(ExchangeContract::class, $this->fake);
    Event::fake([BotSellOrderFilled::class]);
});

/**
 * Binance allows sells up to 2 × the average price; with the default reserve
 * of 10, the bot may hold $maxNumOrders − 10 open orders on the symbol.
 *
 * @param list<array<string,string>> $openOrders
 */
function wtFakeBinance(string $avgPrice, array $openOrders = [], int $maxNumOrders = 200): void
{
    Cache::flush();
    Http::preventStrayRequests();
    Http::fake([
        '*/api/v3/exchangeInfo*' => Http::response(['symbols' => [['filters' => [
            ['filterType' => 'LOT_SIZE', 'stepSize' => '0.01', 'minQty' => '0.01'],
            ['filterType' => 'PRICE_FILTER', 'tickSize' => '0.001'],
            ['filterType' => 'PERCENT_PRICE_BY_SIDE', 'askMultiplierUp' => '2', 'askMultiplierDown' => '0.2', 'avgPriceMins' => 5],
            ['filterType' => 'MAX_NUM_ORDERS', 'maxNumOrders' => $maxNumOrders],
        ]]]]),
        '*/api/v3/avgPrice*'   => Http::response(['mins' => 5, 'price' => $avgPrice]),
        '*/api/v3/time*'       => Http::response(['serverTime' => 1_700_000_000_000]),
        '*/api/v3/openOrders*' => Http::response($openOrders),
    ]);
}

function wtOpenOrder(string $orderId, string $executedQty = '0'): array
{
    return ['orderId' => $orderId, 'executedQty' => $executedQty];
}

function wtPlacedTier(BotBuyExecution $execution, string $percent, string $orderId): BotSellOrder
{
    return BotSellOrder::create([
        'bot_buy_execution_id' => $execution->id,
        'exchange'             => BotSellOrder::EXCHANGE_BINANCE,
        'exchange_order_id'    => $orderId,
        'target_type'          => 'percent',
        'target_value'         => $percent,
        'share_percent'        => '50',
        'amount_to_sell'       => '10',
        'status'               => BotSellOrder::STATUS_OPEN,
        'placement_status'     => BotSellOrder::PLACEMENT_PLACED,
    ]);
}

function wtCurrency(): Currency
{
    return Currency::forceCreate(['name' => 'IOST', 'persian_name' => 'IOST', 'symbol' => 'IOST']);
}

function wtExecution(Currency $currency, array $sellTargets = []): BotBuyExecution
{
    if ($sellTargets !== []) {
        BotSignal::create([
            'currency_id'                  => $currency->id,
            'is_active'                    => true,
            'priority'                     => 1,
            'min_buy_amount_usdt'          => 5,
            'max_buy_amount_usdt'          => 1000,
            'floor_price'                  => 1,
            'ceiling_price'                => 1000,
            'buy_price'                    => 100,
            'sell_orders_count'            => count($sellTargets),
            'sell_mode'                    => 'percent',
            'sell_targets'                 => $sellTargets,
            'p2p_min_order_value_override' => '5',
        ]);
    }

    $order = BotOrder::create([
        'user_id'           => User::factory()->create()->id,
        'batch_uuid'        => (string) \Illuminate\Support\Str::uuid(),
        'total_amount_usdt' => '2000',
        'alpha_snapshot'    => 0.15,
        'status'            => 'COMPLETED',
        'triggered_by'      => 'MANUAL',
    ]);

    return BotBuyExecution::create([
        'bot_order_id'    => $order->id,
        'currency_id'     => $currency->id,
        'signal_snapshot' => ['p2p_min_order_value' => '5'],
        'allocated_usdt'  => '2000',
        'filled_amount'   => '20',
        'avg_buy_price'   => 100,
        'exchange_fee'    => 0,
        'network_fee'     => 0,
        'status'          => BotBuyExecution::STATUS_BOUGHT,
    ]);
}

function wtWaitingTier(
    BotBuyExecution $execution,
    string $percent,
    string $amount = '10',
    string $reason = BotSellOrder::WAITING_PRICE_CEILING,
): BotSellOrder {
    return BotSellOrder::create([
        'bot_buy_execution_id' => $execution->id,
        'exchange'             => BotSellOrder::EXCHANGE_BINANCE,
        'target_type'          => 'percent',
        'target_value'         => $percent,
        'share_percent'        => '50',
        'amount_to_sell'       => $amount,
        'status'               => BotSellOrder::STATUS_OPEN,
        'placement_status'     => BotSellOrder::PLACEMENT_WAITING,
        'waiting_reason'       => $reason,
        'waiting_since'        => now(),
    ]);
}

it('keeps a new buy\'s tiers above the Binance ceiling WAITING instead of failing the buy', function () {
    wtFakeBinance('100'); // ceiling 200, placeable up to 196 (2% margin)
    $execution = wtExecution(wtCurrency(), [
        ['trigger' => 20, 'share' => 50],  // 120 USDT
        ['trigger' => 150, 'share' => 50], // 250 USDT
    ]);

    (new OpenSellOrdersJob($execution->id))->handle(new TargetCollapseService(), $this->fake);

    $tiers = BotSellOrder::where('bot_buy_execution_id', $execution->id)->orderBy('id')->get();
    expect($tiers)->toHaveCount(2)
        ->and($execution->fresh()->status)->toBe(BotBuyExecution::STATUS_BOUGHT)
        ->and($this->fake->limitSells)->toHaveCount(1)
        ->and((float) $this->fake->limitSells[0]['price'])->toBe(120.0);

    expect($tiers[0]->fresh()->placement_status)->toBe(BotSellOrder::PLACEMENT_PLACED)
        ->and($tiers[0]->exchange_order_id)->not->toBeNull()
        ->and($tiers[1]->status)->toBe(BotSellOrder::STATUS_OPEN)
        ->and($tiers[1]->placement_status)->toBe(BotSellOrder::PLACEMENT_WAITING)
        ->and($tiers[1]->exchange_order_id)->toBeNull()
        ->and($tiers[1]->waiting_since)->not->toBeNull()
        ->and($tiers[1]->last_placement_error)->toContain('above Binance ceiling');
});

it('turns a PERCENT_PRICE rejection into a WAITING tier without rolling back the others', function () {
    $this->fake->exchangeName = 'fake'; // no ceiling lookup: only Binance's answer tells
    $this->fake->failLimitSellAt = 2;
    $this->fake->limitSellError = ['-1013', 'Filter failure: PERCENT_PRICE_BY_SIDE'];
    $execution = wtExecution(wtCurrency(), [
        ['trigger' => 20, 'share' => 50],
        ['trigger' => 150, 'share' => 50],
    ]);

    (new OpenSellOrdersJob($execution->id))->handle(new TargetCollapseService(), $this->fake);

    $tiers = BotSellOrder::where('bot_buy_execution_id', $execution->id)->orderBy('id')->get();
    expect($execution->fresh()->status)->toBe(BotBuyExecution::STATUS_BOUGHT)
        ->and($this->fake->cancels)->toBeEmpty()
        ->and($tiers->pluck('status')->unique()->all())->toBe([BotSellOrder::STATUS_OPEN])
        ->and($tiers[1]->placement_status)->toBe(BotSellOrder::PLACEMENT_WAITING)
        ->and($tiers[1]->last_placement_error)->toContain('PERCENT_PRICE');
});

it('places waiting tiers once the average price brings them under the ceiling, lowest first', function () {
    $currency  = wtCurrency();
    $execution = wtExecution($currency);
    $near      = wtWaitingTier($execution, '150'); // 250 USDT
    $far       = wtWaitingTier($execution, '300'); // 400 USDT
    wtFakeBinance('130'); // ceiling 260, placeable up to 254.8
    Cache::put('market:price:IOSTUSDT', '130');

    $this->artisan('bot:place-waiting-sells')->assertSuccessful();

    expect($this->fake->limitSells)->toHaveCount(1)
        ->and((float) $this->fake->limitSells[0]['price'])->toBe(250.0);

    $near->refresh();
    $far->refresh();
    expect($near->placement_status)->toBe(BotSellOrder::PLACEMENT_PLACED)
        ->and($near->exchange_order_id)->toBe('1000')
        ->and($near->exchange)->toBe(BotSellOrder::EXCHANGE_BINANCE)
        ->and($near->placement_attempts)->toBe(1)
        ->and($far->placement_status)->toBe(BotSellOrder::PLACEMENT_WAITING)
        ->and($far->placement_attempts)->toBe(0)
        ->and($far->last_placement_check_at)->not->toBeNull();

    $lastRun = app(WaitingSellTiersService::class)->lastRun();
    expect($lastRun['totals']['placed'])->toBe(1)
        ->and($lastRun['totals']['still_waiting'])->toBe(1);
});

it('market-sells a waiting tier whose target the price already passed', function () {
    $currency  = wtCurrency();
    $tier      = wtWaitingTier(wtExecution($currency), '150'); // 250 USDT
    $this->fake->marketSellAvgPrice = '260';
    wtFakeBinance('100');
    Cache::put('market:price:IOSTUSDT', '260');

    $this->artisan('bot:place-waiting-sells')->assertSuccessful();

    expect($this->fake->limitSells)->toBeEmpty()
        ->and($this->fake->marketSells)->toHaveCount(1)
        ->and($tier->fresh()->placement_status)->toBe(BotSellOrder::PLACEMENT_PLACED);

    Event::assertDispatched(BotSellOrderFilled::class, fn (BotSellOrderFilled $e) => $e->sellOrder->id === $tier->id
        && $e->fillPrice === '260');
});

it('keeps a tier WAITING and records the error when Binance still rejects it', function () {
    $tier = wtWaitingTier(wtExecution(wtCurrency()), '150');
    wtFakeBinance('130');
    Cache::put('market:price:IOSTUSDT', '130');
    $this->fake->failNextLimitSell = true;
    $this->fake->limitSellError = ['-1013', 'Filter failure: PERCENT_PRICE_BY_SIDE'];

    $this->artisan('bot:place-waiting-sells')->assertSuccessful();

    $tier->refresh();
    expect($tier->placement_status)->toBe(BotSellOrder::PLACEMENT_WAITING)
        ->and($tier->exchange_order_id)->toBeNull()
        ->and($tier->placement_attempts)->toBe(1)
        ->and($tier->last_placement_error)->toContain('PERCENT_PRICE');
});

it('does nothing while auto placement is off unless forced', function () {
    $settings = BotGlobalSettings::current();
    $settings->waiting_sells_auto_place = false;
    $settings->save();

    $tier = wtWaitingTier(wtExecution(wtCurrency()), '150');
    wtFakeBinance('130');
    Cache::put('market:price:IOSTUSDT', '130');

    $this->artisan('bot:place-waiting-sells')->assertSuccessful();
    expect($this->fake->limitSells)->toBeEmpty()
        ->and(app(WaitingSellTiersService::class)->lastRun()['disabled'])->toBeTrue();

    $this->artisan('bot:place-waiting-sells --force')->assertSuccessful();
    expect($this->fake->limitSells)->toHaveCount(1)
        ->and($tier->fresh()->placement_status)->toBe(BotSellOrder::PLACEMENT_PLACED);
});

it('skips a coin while its currency cancel is queued', function () {
    config(['smart-bot.internal_admin_token' => 'secret']);
    $currency = wtCurrency();
    $tier     = wtWaitingTier(wtExecution($currency), '150');
    BotCurrencyCancellation::create([
        'currency_id'        => $currency->id,
        'status'             => BotCurrencyCancellation::STATUS_PENDING,
        'reason'             => 'delist',
        'cancel_on_exchange' => true,
        'sell_on_exchange'   => true,
    ]);
    wtFakeBinance('130');
    Cache::put('market:price:IOSTUSDT', '130');

    $this->artisan('bot:place-waiting-sells')->assertSuccessful();
    $this->postJson('/api/internal/bot-admin/waiting-sells/'.$tier->id.'/place', [], ['X-Internal-Token' => 'secret'])
        ->assertStatus(409);

    expect($this->fake->limitSells)->toBeEmpty()
        ->and($tier->fresh()->placement_status)->toBe(BotSellOrder::PLACEMENT_WAITING);
});

it('places one tier on admin request through the internal endpoint', function () {
    config(['smart-bot.internal_admin_token' => 'secret']);
    $tier = wtWaitingTier(wtExecution(wtCurrency()), '150');
    wtFakeBinance('130');
    Cache::put('market:price:IOSTUSDT', '130');

    $this->postJson('/api/internal/bot-admin/waiting-sells/'.$tier->id.'/place', [], ['X-Internal-Token' => 'secret'])
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('result.outcome', 'placed');

    expect($tier->fresh()->placement_status)->toBe(BotSellOrder::PLACEMENT_PLACED);
});

it('keeps a new buy\'s tiers WAITING (order_limit) once Binance reports MAX_NUM_ORDERS', function () {
    $this->fake->exchangeName = 'fake';
    $this->fake->failLimitSellAt = 2;
    $this->fake->limitSellError = ['-2010', 'Filter failure: MAX_NUM_ORDERS'];
    $execution = wtExecution(wtCurrency(), [
        ['trigger' => 20, 'share' => 40],
        ['trigger' => 50, 'share' => 30],
        ['trigger' => 80, 'share' => 30],
    ]);

    (new OpenSellOrdersJob($execution->id))->handle(new TargetCollapseService(), $this->fake);

    $tiers = BotSellOrder::where('bot_buy_execution_id', $execution->id)->orderBy('id')->get();
    expect($execution->fresh()->status)->toBe(BotBuyExecution::STATUS_BOUGHT)
        ->and($this->fake->limitSells)->toHaveCount(2)
        ->and($this->fake->cancels)->toBeEmpty()
        ->and($tiers[0]->placement_status)->toBe(BotSellOrder::PLACEMENT_PLACED)
        ->and($tiers[1]->placement_status)->toBe(BotSellOrder::PLACEMENT_WAITING)
        ->and($tiers[1]->waiting_reason)->toBe(BotSellOrder::WAITING_ORDER_LIMIT)
        ->and($tiers[2]->placement_status)->toBe(BotSellOrder::PLACEMENT_WAITING)
        ->and($tiers[2]->waiting_reason)->toBe(BotSellOrder::WAITING_ORDER_LIMIT)
        ->and($tiers[2]->last_placement_error)->toContain('MAX_NUM_ORDERS');
});

it('places waiting tiers only into the free open-order slots and marks the rest order_limit', function () {
    $execution = wtExecution(wtCurrency());
    $a = wtWaitingTier($execution, '110'); // 210
    $b = wtWaitingTier($execution, '120'); // 220
    $c = wtWaitingTier($execution, '130'); // 230
    $d = wtWaitingTier($execution, '200'); // 300: above the ceiling
    wtFakeBinance('130', [], maxNumOrders: 12); // 12 − 10 reserve = 2 free slots
    Cache::put('market:price:IOSTUSDT', '130');

    $this->artisan('bot:place-waiting-sells')->assertSuccessful();

    expect($this->fake->limitSells)->toHaveCount(2)
        ->and($a->fresh()->placement_status)->toBe(BotSellOrder::PLACEMENT_PLACED)
        ->and($b->fresh()->placement_status)->toBe(BotSellOrder::PLACEMENT_PLACED)
        ->and($c->fresh()->placement_status)->toBe(BotSellOrder::PLACEMENT_WAITING)
        ->and($c->fresh()->waiting_reason)->toBe(BotSellOrder::WAITING_ORDER_LIMIT)
        ->and($d->fresh()->waiting_reason)->toBe(BotSellOrder::WAITING_PRICE_CEILING);

    $run = app(WaitingSellTiersService::class)->lastRun();
    expect($run['totals']['slot_waiting'])->toBe(1)
        ->and($run['totals']['price_waiting'])->toBe(1)
        ->and($run['currencies'][0]['slots'])->toMatchArray(['max' => 12, 'reserve' => 10, 'open' => 0, 'free' => 2]);
});

it('takes the slot reserve and rotation switch from the bot global settings', function () {
    $settings = BotGlobalSettings::current();
    $settings->waiting_order_slot_reserve = 11;
    $settings->waiting_rotation_enabled = false;
    $settings->save();

    $execution = wtExecution(wtCurrency());
    wtPlacedTier($execution, '400', '5000'); // 500
    $a = wtWaitingTier($execution, '110'); // 210
    $b = wtWaitingTier($execution, '120'); // 220
    wtFakeBinance('130', [wtOpenOrder('5000')], maxNumOrders: 13); // 13 − 11 reserve − 1 open = 1 free slot
    Cache::put('market:price:IOSTUSDT', '130');

    $this->artisan('bot:place-waiting-sells')->assertSuccessful();

    expect($this->fake->limitSells)->toHaveCount(1)
        ->and($this->fake->cancels)->toBeEmpty()
        ->and($a->fresh()->placement_status)->toBe(BotSellOrder::PLACEMENT_PLACED)
        ->and($b->fresh()->waiting_reason)->toBe(BotSellOrder::WAITING_ORDER_LIMIT)
        ->and(app(WaitingSellTiersService::class)->lastRun()['currencies'][0]['slots'])
        ->toMatchArray(['max' => 13, 'reserve' => 11, 'open' => 1, 'free' => 1]);
});

it('rotates the farthest untouched bot order out to place a nearer waiting tier', function () {
    $execution = wtExecution(wtCurrency());
    $far  = wtPlacedTier($execution, '400', '5000'); // 500
    $near = wtWaitingTier($execution, '150', reason: BotSellOrder::WAITING_ORDER_LIMIT); // 250
    wtFakeBinance('130', [wtOpenOrder('5000'), wtOpenOrder('9999')], maxNumOrders: 12); // full
    Cache::put('market:price:IOSTUSDT', '130');
    $this->fake->setStatus('5000', new \App\Services\Bot\ReferenceExchange\ExchangeOrderResult('5000', \App\Services\Bot\ReferenceExchange\ExchangeOrderStatus::CANCELED));

    $this->artisan('bot:place-waiting-sells')->assertSuccessful();

    expect($this->fake->cancels)->toHaveCount(1)
        ->and($this->fake->cancels[0]['exchangeOrderId'])->toBe('5000')
        ->and($this->fake->limitSells)->toHaveCount(1)
        ->and((float) $this->fake->limitSells[0]['price'])->toBe(250.0);

    $far->refresh();
    expect($far->status)->toBe(BotSellOrder::STATUS_OPEN)
        ->and($far->placement_status)->toBe(BotSellOrder::PLACEMENT_WAITING)
        ->and($far->waiting_reason)->toBe(BotSellOrder::WAITING_ORDER_LIMIT)
        ->and($far->exchange_order_id)->toBeNull()
        ->and($near->fresh()->placement_status)->toBe(BotSellOrder::PLACEMENT_PLACED)
        ->and(app(WaitingSellTiersService::class)->lastRun()['totals']['rotated'])->toBe(1);
});

it('never rotates out a partly filled order or one too close to the waiting tier', function () {
    $execution = wtExecution(wtCurrency());
    $partial = wtPlacedTier($execution, '400', '5000'); // 500, partly filled
    $close   = wtPlacedTier($execution, '160', '5001'); // 260: within the 10% gap of 250
    $near    = wtWaitingTier($execution, '150', reason: BotSellOrder::WAITING_ORDER_LIMIT);
    wtFakeBinance('130', [wtOpenOrder('5000', '1'), wtOpenOrder('5001')], maxNumOrders: 12);
    Cache::put('market:price:IOSTUSDT', '130');

    $this->artisan('bot:place-waiting-sells')->assertSuccessful();

    expect($this->fake->cancels)->toBeEmpty()
        ->and($this->fake->limitSells)->toBeEmpty()
        ->and($partial->fresh()->placement_status)->toBe(BotSellOrder::PLACEMENT_PLACED)
        ->and($close->fresh()->placement_status)->toBe(BotSellOrder::PLACEMENT_PLACED)
        ->and($near->fresh()->waiting_reason)->toBe(BotSellOrder::WAITING_ORDER_LIMIT);
});

it('forecasts the price at which each waiting tier gets a slot or fits under the ceiling', function () {
    $currency  = wtCurrency();
    $execution = wtExecution($currency);
    wtPlacedTier($execution, '20', '5000'); // 120
    wtPlacedTier($execution, '30', '5001'); // 130
    $now   = wtWaitingTier($execution, '40', reason: BotSellOrder::WAITING_ORDER_LIMIT);  // 140, rank 3
    $slotA = wtWaitingTier($execution, '50', reason: BotSellOrder::WAITING_ORDER_LIMIT);  // 150, rank 4
    $slotB = wtWaitingTier($execution, '60', reason: BotSellOrder::WAITING_ORDER_LIMIT);  // 160, rank 5
    $price = wtWaitingTier($execution, '200');                                           // 300, rank 6
    wtFakeBinance('100', [wtOpenOrder('5000'), wtOpenOrder('5001')], maxNumOrders: 13); // bot capacity 3
    Cache::put('market:price:IOSTUSDT', '100');

    $d     = app(WaitingSellTiersService::class)->details($currency);
    $tiers = collect($d['tiers'])->keyBy('sell_order_id');

    expect($d['slots'])->toMatchArray(['max' => 13, 'open' => 2, 'bot_placed' => 2, 'others' => 0, 'bot_capacity' => 3])
        ->and($tiers[$now->id]['binding'])->toBe('now')
        ->and($tiers[$slotA->id]['binding'])->toBe('slot')
        ->and((float) $tiers[$slotA->id]['forecast_price'])->toBe(120.0)
        ->and($tiers[$slotA->id]['lower_to_sell'])->toBe(1)
        ->and((float) $tiers[$slotB->id]['forecast_price'])->toBe(130.0)
        ->and($tiers[$price->id]['binding'])->toBe('ceiling')
        ->and(round((float) $tiers[$price->id]['forecast_price'], 2))->toBe(153.06)
        ->and($d['groups']['order_limit']['count'])->toBe(3)
        ->and($d['groups']['order_limit']['placeable_now'])->toBe(1)
        ->and((float) $d['groups']['order_limit']['last_price'])->toBe(130.0)
        ->and($d['groups']['price_ceiling']['count'])->toBe(1);

    $m = collect($d['milestones'])->keyBy('rise_percent');
    expect($m[0]['total'])->toBe(1)
        ->and($m[30]['order_limit'])->toBe(3)
        ->and($m[30]['price_ceiling'])->toBe(0)
        ->and($m[75]['total'])->toBe(4);
});
