<?php

use App\Events\Bot\BotSellOrderFilled;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellMigration;
use App\Models\Bot\BotSellOrder;
use App\Models\Currency;
use App\Models\User;
use App\Services\Bot\BotSellMigrationService;
use App\Services\Bot\ReferenceExchange\ExchangeContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakeExchange;

uses(RefreshDatabase::class);

beforeEach(function () {
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
        ]]]]),
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
