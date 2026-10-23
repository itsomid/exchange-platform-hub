<?php

use App\Events\Bot\BotSellOrderFilled;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellMigration;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotTradeSettlement;
use App\Models\Bot\BotWallet;
use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Bot\BotSellMigrationService;
use App\Services\Bot\ReferenceExchange\ExchangeContract;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakeExchange;

uses(RefreshDatabase::class);

beforeEach(function () {
    // transactions.coin_price exists in the real schema but no migration creates it.
    if (! Schema::hasColumn('transactions', 'coin_price')) {
        Schema::table('transactions', fn (Blueprint $t) => $t->decimal('coin_price', 30, 8)->nullable());
    }

    $exchangeUser = User::factory()->create();
    config(['bitexroom.user_id' => $exchangeUser->id]);
    Wallet::create([
        'user_id'         => $exchangeUser->id,
        'currency_symbol' => 'USDT',
        'balance'         => '0',
        'locked_balance'  => '0',
    ]);

    $this->fake = new FakeExchange();
    $this->app->instance(ExchangeContract::class, $this->fake);
    Event::fake([BotSellOrderFilled::class]);
});

function smFakeBinance(string $asset, string $free): void
{
    Http::preventStrayRequests();
    Http::fake([
        '*/api/v3/time*'         => Http::response(['serverTime' => 1_700_000_000_000]),
        '*/api/v3/exchangeInfo*' => Http::response(['symbols' => [['filters' => [
            ['filterType' => 'LOT_SIZE', 'stepSize' => '0.01', 'minQty' => '0.01'],
            ['filterType' => 'PRICE_FILTER', 'tickSize' => '0.001'],
            ['filterType' => 'NOTIONAL', 'minNotional' => '5', 'applyMinToMarket' => true],
            ['filterType' => 'PERCENT_PRICE_BY_SIDE', 'askMultiplierUp' => '5', 'askMultiplierDown' => '0.2', 'avgPriceMins' => 5],
        ]]]]),
        '*/api/v3/avgPrice*'     => Http::response(['mins' => 5, 'price' => '10']),
        '*/api/v3/account*'      => Http::response(['balances' => [
            ['asset' => $asset, 'free' => $free, 'locked' => '0'],
        ]]),
    ]);
}

/**
 * One BOUGHT execution of $currency at $avg with the given tiers, each
 * [target_value(percent), amount, status].
 *
 * @param array<int,array{0:string,1:string,2:string}> $tiers
 * @return array<int,BotSellOrder>
 */
function smPosition(Currency $currency, string $avg, array $tiers): array
{
    $user  = User::factory()->create();
    BotWallet::create([
        'user_id'           => $user->id,
        'balance'           => '100',
        'principal_balance' => '100',
        'profit_balance'    => '0',
        'locked_balance'    => '100',
    ]);
    $order = BotOrder::create([
        'user_id'           => $user->id,
        'batch_uuid'        => (string) \Illuminate\Support\Str::uuid(),
        'total_amount_usdt' => '100',
        'alpha_snapshot'    => 0.15,
        'status'            => 'PENDING',
        'triggered_by'      => 'MANUAL',
    ]);
    $execution = BotBuyExecution::create([
        'bot_order_id'    => $order->id,
        'currency_id'     => $currency->id,
        'signal_snapshot' => [],
        'allocated_usdt'  => '100',
        'filled_amount'   => '10',
        'avg_buy_price'   => $avg,
        'status'          => BotBuyExecution::STATUS_BOUGHT,
    ]);

    $rows = [];
    foreach ($tiers as $i => [$target, $amount, $status]) {
        $rows[] = BotSellOrder::create([
            'bot_buy_execution_id' => $execution->id,
            'exchange_order_id'    => (string) (17914920817113505 + $i),
            'exchange'             => BotSellOrder::EXCHANGE_COINEX,
            'target_type'          => 'percent',
            'target_value'         => $target,
            'share_percent'        => '50',
            'amount_to_sell'       => $amount,
            'status'               => $status,
        ]);
    }

    return $rows;
}

function smCurrency(): Currency
{
    return Currency::forceCreate(['name' => 'AVAX', 'persian_name' => 'AVAX', 'symbol' => 'AVAX']);
}

it('re-places an unreached tier as a Binance limit sell by editing the same row', function () {
    $avax = smCurrency();
    [$tier] = smPosition($avax, '10', [['10', '5', BotSellOrder::STATUS_OPEN]]);
    Cache::put('market:price:AVAXUSDT', '10.5');
    smFakeBinance('AVAX', '20');

    $migration = app(BotSellMigrationService::class)->start($avax, 1, 'admin@test')->fresh();

    expect($migration->status)->toBe(BotSellMigration::STATUS_DONE)
        ->and($migration->placed_count)->toBe(1)
        ->and($migration->completed_count)->toBe(0)
        ->and(BotSellOrder::count())->toBe(1);

    expect($this->fake->limitSells)->toHaveCount(1)
        ->and($this->fake->limitSells[0]['market'])->toBe('AVAXUSDT')
        ->and((float) $this->fake->limitSells[0]['price'])->toBe(11.0)
        ->and($this->fake->marketSells)->toBeEmpty();

    $tier->refresh();
    expect($tier->status)->toBe(BotSellOrder::STATUS_OPEN)
        ->and($tier->exchange)->toBe('fake')
        ->and($tier->previous_exchange_order_id)->toBe('17914920817113505')
        ->and($tier->exchange_order_id)->toBe('1000')
        ->and($tier->migrated_at)->not->toBeNull();

    Event::assertNotDispatched(BotSellOrderFilled::class);
});

it('market-sells a tier whose target was already reached and settles it through the fill event', function () {
    $avax = smCurrency();
    [$tier] = smPosition($avax, '10', [['10', '5', BotSellOrder::STATUS_OPEN]]);
    Cache::put('market:price:AVAXUSDT', '111');
    $this->fake->marketSellAvgPrice = '111';
    smFakeBinance('AVAX', '20');

    $migration = app(BotSellMigrationService::class)->start($avax, 1, 'admin@test')->fresh();

    expect($migration->completed_count)->toBe(1)
        ->and($this->fake->limitSells)->toBeEmpty()
        ->and($this->fake->marketSells)->toHaveCount(1);

    $tier->refresh();
    expect($tier->exchange)->toBe('fake')
        ->and($tier->previous_exchange_order_id)->toBe('17914920817113505');

    Event::assertDispatched(BotSellOrderFilled::class, fn (BotSellOrderFilled $e) => $e->sellOrder->id === $tier->id
        && (float) $e->filledAmount === 5.0
        && $e->fillPrice === '111');
});

it('never touches FILLED or CANCELED tiers', function () {
    $avax = smCurrency();
    [$open, $filled, $canceled] = smPosition($avax, '10', [
        ['20', '3', BotSellOrder::STATUS_OPEN],
        ['5', '3', BotSellOrder::STATUS_FILLED],
        ['8', '3', BotSellOrder::STATUS_CANCELED],
    ]);
    Cache::put('market:price:AVAXUSDT', '10.5');
    smFakeBinance('AVAX', '3');

    $preview = app(BotSellMigrationService::class)->preview($avax);
    expect($preview['totals']['tiers'])->toBe(1)
        ->and($preview['required_amount'])->toBe('3.00000000')
        ->and($preview['filled_tiers'])->toBe(1)
        ->and($preview['can_start'])->toBeTrue();

    app(BotSellMigrationService::class)->start($avax, 1, 'admin@test');

    expect($this->fake->limitSells)->toHaveCount(1)
        ->and($filled->fresh()->exchange)->toBe(BotSellOrder::EXCHANGE_COINEX)
        ->and($canceled->fresh()->exchange)->toBe(BotSellOrder::EXCHANGE_COINEX)
        ->and($open->fresh()->exchange)->toBe('fake');
});

it('refuses to start when the free Binance balance is below what the tiers need', function () {
    $avax = smCurrency();
    smPosition($avax, '10', [['10', '5', BotSellOrder::STATUS_OPEN], ['20', '5', BotSellOrder::STATUS_OPEN]]);
    Cache::put('market:price:AVAXUSDT', '10.5');
    smFakeBinance('AVAX', '7');

    $preview = app(BotSellMigrationService::class)->preview($avax);
    expect($preview['can_start'])->toBeFalse()
        ->and($preview['sufficient'])->toBeFalse()
        ->and((float) $preview['shortfall'])->toBe(3.0);

    expect(fn () => app(BotSellMigrationService::class)->start($avax, 1, 'admin@test'))
        ->toThrow(\DomainException::class);

    expect(BotSellMigration::count())->toBe(0)
        ->and($this->fake->limitSells)->toBeEmpty();
});

it('keeps limit tiers priced above Binance\'s sell ceiling waiting instead of placing them', function () {
    $avax = smCurrency();
    [$near, $far] = smPosition($avax, '10', [
        ['20', '5', BotSellOrder::STATUS_OPEN],   // 12 USDT, under 10 × 5
        ['1000', '5', BotSellOrder::STATUS_OPEN], // 110 USDT, over the 50 USDT ceiling
    ]);
    Cache::put('market:price:AVAXUSDT', '10.5');
    smFakeBinance('AVAX', '20');

    $preview = app(BotSellMigrationService::class)->preview($avax);
    $byId    = collect($preview['tiers'])->keyBy('sell_order_id');

    expect($preview['price_limit']['multiplier_up'])->toBe('5')
        ->and($preview['price_limit']['reference'])->toBe('avg')
        ->and((float) $preview['price_limit']['max_price'])->toBe(50.0)
        ->and($preview['price_limit']['max_percent_above'])->toBe('400.00')
        ->and($preview['totals']['wait_tiers'])->toBe(1)
        ->and($byId[$near->id]['action'])->toBe('limit')
        ->and($byId[$far->id]['action'])->toBe('wait')
        ->and($byId[$near->id]['price_limit_exceeded'])->toBeFalse()
        ->and($byId[$far->id]['price_limit_exceeded'])->toBeTrue()
        ->and($byId[$far->id]['over_max_percent'])->toBe('120.00')
        ->and($byId[$far->id]['above_live_percent'])->toBe('947.61');

    $migration = app(BotSellMigrationService::class)->start($avax, 1, 'admin@test')->fresh();

    expect($migration->placed_count)->toBe(1)
        ->and($migration->waiting_count)->toBe(1)
        ->and($migration->failed_count)->toBe(0)
        ->and($this->fake->limitSells)->toHaveCount(1);

    $far->refresh();
    expect($far->status)->toBe(BotSellOrder::STATUS_OPEN)
        ->and($far->placement_status)->toBe(BotSellOrder::PLACEMENT_WAITING)
        ->and($far->exchange)->toBe('fake')
        ->and($far->exchange_order_id)->toBeNull()
        ->and($far->previous_exchange_order_id)->toBe('17914920817113506')
        ->and($far->migrated_at)->not->toBeNull();
});

it('marks a tier waiting when Binance rejects it for PERCENT_PRICE during the run', function () {
    $avax = smCurrency();
    [$tier] = smPosition($avax, '10', [['10', '5', BotSellOrder::STATUS_OPEN]]);
    Cache::put('market:price:AVAXUSDT', '10.5');
    smFakeBinance('AVAX', '20');
    $this->fake->failNextLimitSell = true;
    $this->fake->limitSellError = ['-1013', 'Filter failure: PERCENT_PRICE_BY_SIDE'];

    $migration = app(BotSellMigrationService::class)->start($avax, 1, 'admin@test')->fresh();

    expect($migration->waiting_count)->toBe(1)
        ->and($migration->failed_count)->toBe(0)
        ->and($tier->fresh()->placement_status)->toBe(BotSellOrder::PLACEMENT_WAITING)
        ->and($tier->fresh()->exchange)->toBe('fake')
        ->and($tier->fresh()->last_placement_error)->toContain('PERCENT_PRICE');
});

it('keeps the highest limit tiers waiting (order_limit) when Binance has too few free order slots', function () {
    $avax = smCurrency();
    $this->fake->exchangeName = BotSellOrder::EXCHANGE_BINANCE;
    [$a, $b, $c] = smPosition($avax, '10', [
        ['10', '5', BotSellOrder::STATUS_OPEN], // 11
        ['30', '5', BotSellOrder::STATUS_OPEN], // 13
        ['20', '5', BotSellOrder::STATUS_OPEN], // 12
    ]);
    Cache::put('market:price:AVAXUSDT', '10.5');
    smFakeBinance('AVAX', '20');
    // 200 − 10 reserve − 189 open = 1 free slot
    Http::fake(['*/api/v3/openOrders*' => Http::response(
        array_map(fn (int $i) => ['orderId' => (string) $i, 'executedQty' => '0'], range(1, 189)),
    )]);

    $preview = app(BotSellMigrationService::class)->preview($avax);
    $byId    = collect($preview['tiers'])->keyBy('sell_order_id');

    expect($preview['order_slots'])->toMatchArray(['max' => 200, 'reserve' => 10, 'open' => 189, 'free' => 1])
        ->and($preview['totals']['wait_slot_tiers'])->toBe(2)
        ->and($preview['totals']['wait_price_tiers'])->toBe(0)
        ->and($byId[$a->id]['action'])->toBe('limit')
        ->and($byId[$c->id]['wait_reason'])->toBe(BotSellOrder::WAITING_ORDER_LIMIT)
        ->and($byId[$b->id]['wait_reason'])->toBe(BotSellOrder::WAITING_ORDER_LIMIT)
        ->and($byId[$b->id]['price_limit_exceeded'])->toBeFalse();

    $migration = app(BotSellMigrationService::class)->start($avax, 1, 'admin@test')->fresh();

    expect($migration->placed_count)->toBe(1)
        ->and($migration->waiting_count)->toBe(2)
        ->and($this->fake->limitSells)->toHaveCount(1)
        ->and((float) $this->fake->limitSells[0]['price'])->toBe(11.0)
        ->and($b->fresh()->placement_status)->toBe(BotSellOrder::PLACEMENT_WAITING)
        ->and($b->fresh()->waiting_reason)->toBe(BotSellOrder::WAITING_ORDER_LIMIT)
        ->and($b->fresh()->last_placement_error)->toContain('MAX_NUM_ORDERS');
});

it('marks a tier waiting (order_limit) when Binance rejects it for MAX_NUM_ORDERS during the run', function () {
    $avax = smCurrency();
    [$tier] = smPosition($avax, '10', [['10', '5', BotSellOrder::STATUS_OPEN]]);
    Cache::put('market:price:AVAXUSDT', '10.5');
    smFakeBinance('AVAX', '20');
    $this->fake->failNextLimitSell = true;
    $this->fake->limitSellError = ['-2010', 'Filter failure: MAX_NUM_ORDERS'];

    $migration = app(BotSellMigrationService::class)->start($avax, 1, 'admin@test')->fresh();

    expect($migration->waiting_count)->toBe(1)
        ->and($migration->failed_count)->toBe(0)
        ->and($tier->fresh()->placement_status)->toBe(BotSellOrder::PLACEMENT_WAITING)
        ->and($tier->fresh()->waiting_reason)->toBe(BotSellOrder::WAITING_ORDER_LIMIT);
});

it('merges a below-minimum tier into the lower-target sibling of the same buy', function () {
    $avax = smCurrency();
    [$low, $small] = smPosition($avax, '10', [
        ['10', '5', BotSellOrder::STATUS_OPEN],
        ['20', '0.3', BotSellOrder::STATUS_OPEN], // 0.3 × 12 = 3.6 USDT < 5 minNotional
    ]);
    Cache::put('market:price:AVAXUSDT', '10.5');
    smFakeBinance('AVAX', '20');

    $preview = app(BotSellMigrationService::class)->preview($avax);
    $byId    = collect($preview['tiers'])->keyBy('sell_order_id');
    expect($byId[$small->id]['action'])->toBe('merge')
        ->and($byId[$small->id]['merge_into'])->toBe($low->id)
        ->and($byId[$low->id]['action'])->toBe('limit')
        ->and((float) $byId[$low->id]['merged_amount'])->toBe(5.3)
        ->and($preview['totals']['merge_tiers'])->toBe(1)
        ->and($preview['close'])->toBeNull();

    $migration = app(BotSellMigrationService::class)->start($avax, 1, 'admin@test')->fresh();

    expect($migration->merged_count)->toBe(1)
        ->and($migration->placed_count)->toBe(1)
        ->and($migration->failed_count)->toBe(0);

    expect($this->fake->limitSells)->toHaveCount(1)
        ->and((float) $this->fake->limitSells[0]['baseAmount'])->toBe(5.3)
        ->and((float) $this->fake->limitSells[0]['price'])->toBe(11.0);

    $low->refresh();
    $small->refresh();
    expect((float) $low->amount_to_sell)->toBe(5.3)
        ->and((float) $low->share_percent)->toBe(100.0)
        ->and($low->exchange)->toBe('fake')
        ->and($small->status)->toBe(BotSellOrder::STATUS_CANCELED)
        ->and($small->cancel_reason)->toBe(BotSellOrder::CANCEL_MIGRATION_MERGED)
        ->and(BotTradeSettlement::count())->toBe(0);
});

it('sells unmergeable below-minimum tiers in one market order and refunds with the currency-cancel rule', function () {
    $avax = smCurrency();
    [$a] = smPosition($avax, '10', [['20', '0.3', BotSellOrder::STATUS_OPEN]]);
    [$b] = smPosition($avax, '10', [['20', '0.3', BotSellOrder::STATUS_OPEN]]);
    Cache::put('market:price:AVAXUSDT', '10.5');
    $this->fake->marketSellAvgPrice = '10.5';
    smFakeBinance('AVAX', '20');

    $preview = app(BotSellMigrationService::class)->preview($avax);
    expect($preview['totals']['close_tiers'])->toBe(2)
        ->and($preview['close']['sellable'])->toBeTrue()
        ->and((float) $preview['tiers'][0]['estimated_refund'])->toBe(3.117);

    $migration = app(BotSellMigrationService::class)->start($avax, 1, 'admin@test')->fresh();
    expect($migration->closed_count)->toBe(2)
        ->and($migration->details['close']['price_source'])->toBe('market_sell')
        ->and($this->fake->marketSells)->toHaveCount(1)
        ->and((float) $this->fake->marketSells[0]['baseAmount'])->toBe(0.6)
        ->and($this->fake->limitSells)->toBeEmpty();

    foreach ([$a, $b] as $tier) {
        $tier->refresh();
        $wallet = BotWallet::where('user_id', $tier->botBuyExecution->botOrder->user_id)->first();
        // cost 3, value 3.15, profit 0.15 − 22% fee = 0.117; principal 3 unlocked.
        expect($tier->status)->toBe(BotSellOrder::STATUS_CANCELED)
            ->and($tier->cancel_reason)->toBe(BotSellOrder::CANCEL_MIGRATION_BELOW_MIN)
            ->and($tier->bot_currency_cancellation_id)->toBeNull()
            ->and((float) $wallet->balance)->toBe(100.117)
            ->and((float) $wallet->locked_balance)->toBe(97.0);
    }
    expect(BotTradeSettlement::count())->toBe(2);
    Event::assertNotDispatched(BotSellOrderFilled::class);
});

it('settles dust at the live price without selling when even the aggregated total is below the minimum', function () {
    $avax = smCurrency();
    [$tier] = smPosition($avax, '12', [['50', '0.2', BotSellOrder::STATUS_OPEN]]); // 0.2 × 18 = 3.6 USDT
    Cache::put('market:price:AVAXUSDT', '10.5'); // in loss: refund = principal only
    smFakeBinance('AVAX', '20');

    expect(app(BotSellMigrationService::class)->preview($avax)['close']['sellable'])->toBeFalse();

    $migration = app(BotSellMigrationService::class)->start($avax, 1, 'admin@test')->fresh();

    expect($migration->closed_count)->toBe(1)
        ->and($migration->details['close']['price_source'])->toBe('live')
        ->and($this->fake->marketSells)->toBeEmpty();

    $tier->refresh();
    $wallet = BotWallet::where('user_id', $tier->botBuyExecution->botOrder->user_id)->first();
    expect($tier->status)->toBe(BotSellOrder::STATUS_CANCELED)
        ->and((float) $wallet->balance)->toBe(100.0)
        ->and((float) $wallet->locked_balance)->toBe(98.0);
});

it('settles below-minimum tiers at the live price when the aggregated market sell fails', function () {
    $avax = smCurrency();
    [$a] = smPosition($avax, '10', [['20', '0.3', BotSellOrder::STATUS_OPEN]]);
    smPosition($avax, '10', [['20', '0.3', BotSellOrder::STATUS_OPEN]]);
    Cache::put('market:price:AVAXUSDT', '10.5');
    smFakeBinance('AVAX', '20');
    $this->fake->failNextMarketSell = true;

    $migration = app(BotSellMigrationService::class)->start($avax, 1, 'admin@test')->fresh();

    expect($migration->failed_count)->toBe(0)
        ->and($migration->closed_count)->toBe(2)
        ->and($migration->details['close']['price_source'])->toBe('live')
        ->and($migration->details['close']['sell_error'])->toContain('forced failure')
        ->and($this->fake->marketSells)->toHaveCount(1)
        ->and($a->fresh()->status)->toBe(BotSellOrder::STATUS_CANCELED)
        ->and($a->fresh()->cancel_reason)->toBe(BotSellOrder::CANCEL_MIGRATION_BELOW_MIN)
        ->and(BotTradeSettlement::count())->toBe(2);
});

it('leaves a tier untouched when Binance rejects it and does nothing on a second run', function () {
    $avax = smCurrency();
    [$tier] = smPosition($avax, '10', [['10', '5', BotSellOrder::STATUS_OPEN]]);
    Cache::put('market:price:AVAXUSDT', '10.5');
    smFakeBinance('AVAX', '20');

    $this->fake->failNextLimitSell = true;
    $first = app(BotSellMigrationService::class)->start($avax, 1, 'admin@test')->fresh();
    expect($first->failed_count)->toBe(1)
        ->and($tier->fresh()->exchange)->toBe(BotSellOrder::EXCHANGE_COINEX)
        ->and($tier->fresh()->exchange_order_id)->toBe('17914920817113505');

    app(BotSellMigrationService::class)->start($avax, 1, 'admin@test');
    expect($tier->fresh()->exchange)->toBe('fake');

    expect(fn () => app(BotSellMigrationService::class)->start($avax, 1, 'admin@test'))
        ->toThrow(\DomainException::class);
    expect($this->fake->limitSells)->toHaveCount(2);
});
