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

/** Binance allows sells up to 2 × the average price. */
function wtFakeBinance(string $avgPrice): void
{
    Cache::flush();
    Http::preventStrayRequests();
    Http::fake([
        '*/api/v3/exchangeInfo*' => Http::response(['symbols' => [['filters' => [
            ['filterType' => 'LOT_SIZE', 'stepSize' => '0.01', 'minQty' => '0.01'],
            ['filterType' => 'PRICE_FILTER', 'tickSize' => '0.001'],
            ['filterType' => 'PERCENT_PRICE_BY_SIDE', 'askMultiplierUp' => '2', 'askMultiplierDown' => '0.2', 'avgPriceMins' => 5],
        ]]]]),
        '*/api/v3/avgPrice*' => Http::response(['mins' => 5, 'price' => $avgPrice]),
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

function wtWaitingTier(BotBuyExecution $execution, string $percent, string $amount = '10'): BotSellOrder
{
    return BotSellOrder::create([
        'bot_buy_execution_id' => $execution->id,
        'exchange'             => BotSellOrder::EXCHANGE_BINANCE,
        'target_type'          => 'percent',
        'target_value'         => $percent,
        'share_percent'        => '50',
        'amount_to_sell'       => $amount,
        'status'               => BotSellOrder::STATUS_OPEN,
        'placement_status'     => BotSellOrder::PLACEMENT_WAITING,
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
