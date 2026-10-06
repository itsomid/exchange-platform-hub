<?php

use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotCurrencyCancellation;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotSignal;
use App\Models\Bot\BotTradeSettlement;
use App\Models\Bot\BotWallet;
use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Bot\BotCurrencyCancelService;
use App\Services\Bot\ReferenceExchange\ExchangeContract;
use App\Services\Bot\ReferenceExchange\ExchangeOrderResult;
use App\Services\Bot\ReferenceExchange\ExchangeOrderStatus;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
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

    BotGlobalSettings::create([
        'min_deposit_usdt'          => 20,
        'alpha_weight'              => 0.15,
        'default_sell_orders_count' => 3,
        'performance_fee_percent'   => 20,
        'p2p_min_order_value'       => 5,
        'transfer_fee_tiers'        => [],
        'is_enabled'                => true,
    ]);

    $this->fake = new FakeExchange();
    $this->app->instance(ExchangeContract::class, $this->fake);
});

function ccCurrency(string $symbol): Currency
{
    return Currency::forceCreate(['name' => $symbol, 'persian_name' => $symbol, 'symbol' => $symbol]);
}

function ccSignal(Currency $currency): BotSignal
{
    return BotSignal::create([
        'currency_id'            => $currency->id,
        'priority'               => 1,
        'floor_price'            => '0',
        'ceiling_price'          => '1000000',
        'min_buy_amount_usdt'    => '5',
        'max_allocation_percent' => '100',
        'sell_orders_count'      => 1,
        'sell_mode'              => 'EQUAL',
        'sell_targets'           => [],
        'is_active'              => true,
    ]);
}

/**
 * One user's order holding $amount of $currency bought at $avg, all in a single
 * OPEN tier. Wallet: balance 100, locked = allocated.
 *
 * @return array{user:User, order:BotOrder, execution:BotBuyExecution, sell:BotSellOrder}
 */
function ccPosition(Currency $currency, string $amount, string $avg, ?BotOrder $order = null): array
{
    if (! $order) {
        $user = User::factory()->create();
        BotWallet::create([
            'user_id'           => $user->id,
            'balance'           => '100',
            'principal_balance' => '100',
            'profit_balance'    => '0',
            'locked_balance'    => '0',
        ]);
        $order = BotOrder::create([
            'user_id'           => $user->id,
            'batch_uuid'        => (string) \Illuminate\Support\Str::uuid(),
            'total_amount_usdt' => '100',
            'alpha_snapshot'    => 0.15,
            'status'            => 'PENDING',
            'triggered_by'      => 'MANUAL',
        ]);
    }

    $allocated = bcmul($amount, $avg, 8);
    BotWallet::where('user_id', $order->user_id)->increment('locked_balance', $allocated);

    $execution = BotBuyExecution::create([
        'bot_order_id'    => $order->id,
        'currency_id'     => $currency->id,
        'signal_snapshot' => [],
        'allocated_usdt'  => $allocated,
        'filled_amount'   => $amount,
        'avg_buy_price'   => $avg,
        'status'          => BotBuyExecution::STATUS_BOUGHT,
    ]);

    $sell = BotSellOrder::create([
        'bot_buy_execution_id' => $execution->id,
        'exchange_order_id'    => 'ex-'.$execution->id,
        'target_type'          => 'percent',
        'target_value'         => 20,
        'share_percent'        => 100,
        'amount_to_sell'       => $amount,
        'status'               => BotSellOrder::STATUS_OPEN,
    ]);

    return ['user' => User::find($order->user_id), 'order' => $order, 'execution' => $execution, 'sell' => $sell];
}

function ccStart(Currency $currency, bool $cancelOnExchange = false, bool $sellOnExchange = false): BotCurrencyCancellation
{
    return app(BotCurrencyCancelService::class)
        ->start($currency, $cancelOnExchange, $sellOnExchange, 'delisted on reference exchange', 7, 'admin@test')
        ->fresh();
}

it('refunds principal plus profit minus performance fee, without touching the exchange', function () {
    $x = ccCurrency('XXX');
    $signal = ccSignal($x);
    ['user' => $user, 'order' => $order, 'execution' => $execution, 'sell' => $sell] = ccPosition($x, '10', '2');
    Cache::put('market:price:XXXUSDT', '2.5'); // 20 USDT → 25 USDT

    $run = ccStart($x);

    expect($run->status)->toBe(BotCurrencyCancellation::STATUS_DONE);
    expect($run->details['errors'])->toBe([]);
    expect($run->price_source)->toBe(BotCurrencyCancellation::PRICE_LIVE);
    expect((float) $run->total_refund)->toBe(24.0);
    expect((float) $run->total_performance_fee)->toBe(1.0);

    $wallet = BotWallet::where('user_id', $user->id)->first();
    expect((float) $wallet->locked_balance)->toBe(0.0);
    expect((float) $wallet->balance)->toBe(104.0); // free = 100 − 0 + 4 net profit

    $sell->refresh();
    expect($sell->status)->toBe(BotSellOrder::STATUS_CANCELED);
    expect($sell->cancel_reason)->toBe(BotSellOrder::CANCEL_ADMIN_CURRENCY);
    expect($sell->bot_currency_cancellation_id)->toBe($run->id);

    $settlement = BotTradeSettlement::where('bot_sell_order_id', $sell->id)->first();
    expect((float) $settlement->net_pnl)->toBe(4.0);
    expect((float) $settlement->referral_fee)->toBe(0.0);

    expect($execution->fresh()->status)->toBe(BotBuyExecution::STATUS_CLOSED);
    expect($order->fresh()->status)->toBe('CANCELED');
    expect($order->fresh()->cancel_source)->toBe(BotOrder::CANCEL_SOURCE_ADMIN);
    expect($order->fresh()->description)->toContain('delisted on reference exchange');
    expect($signal->fresh()->is_active)->toBeFalse();

    expect($this->fake->cancels)->toBeEmpty();
    expect($this->fake->marketSells)->toBeEmpty();
});

it('refunds exactly the principal when the coin is at a loss', function () {
    $x = ccCurrency('XXX');
    ['user' => $user] = ccPosition($x, '10', '2');
    Cache::put('market:price:XXXUSDT', '1.5'); // 20 USDT → 15 USDT

    $run = ccStart($x);

    expect((float) $run->total_refund)->toBe(20.0);
    expect((float) $run->total_performance_fee)->toBe(0.0);

    $wallet = BotWallet::where('user_id', $user->id)->first();
    expect((float) $wallet->balance)->toBe(100.0);
    expect((float) $wallet->locked_balance)->toBe(0.0);
});

it('cancels tiers on the exchange and market-sells all users in one order at its fill price', function () {
    $x = ccCurrency('XXX');
    ['user' => $u1, 'sell' => $s1] = ccPosition($x, '10', '2');
    ['user' => $u2, 'sell' => $s2] = ccPosition($x, '5', '2');
    Cache::put('market:price:XXXUSDT', '9');
    $this->fake->marketSellAvgPrice = '3';

    $run = ccStart($x, cancelOnExchange: true, sellOnExchange: true);

    expect($this->fake->cancels)->toHaveCount(2);
    expect($this->fake->marketSells)->toHaveCount(1);
    expect((float) $this->fake->marketSells[0]['baseAmount'])->toBe(15.0);

    expect($run->price_source)->toBe(BotCurrencyCancellation::PRICE_MARKET_SELL);
    expect((float) $run->settlement_price)->toBe(3.0);
    expect($run->users_count)->toBe(2);

    // u1: 20 → 30, profit 10, fee 2 → +8 ; u2: 10 → 15, profit 5, fee 1 → +4
    expect((float) BotWallet::where('user_id', $u1->id)->value('balance'))->toBe(108.0);
    expect((float) BotWallet::where('user_id', $u2->id)->value('balance'))->toBe(104.0);
});

it('only cancels the tiers on the exchange when selling is off', function () {
    $x = ccCurrency('XXX');
    ccPosition($x, '10', '2');
    Cache::put('market:price:XXXUSDT', '2');

    $run = ccStart($x, cancelOnExchange: true);

    expect($this->fake->cancels)->toHaveCount(1);
    expect($this->fake->marketSells)->toBeEmpty();
    expect($run->price_source)->toBe(BotCurrencyCancellation::PRICE_LIVE);
});

it('leaves a tier that already filled on the exchange OPEN for the regular sync', function () {
    $x = ccCurrency('XXX');
    ['sell' => $filled] = ccPosition($x, '10', '2');
    ['sell' => $open]   = ccPosition($x, '5', '2');
    Cache::put('market:price:XXXUSDT', '2');
    $this->fake->setStatus((string) $filled->exchange_order_id, new ExchangeOrderResult(
        (string) $filled->exchange_order_id, ExchangeOrderStatus::FILLED, '10', '2.4',
    ));

    $run = ccStart($x, cancelOnExchange: true, sellOnExchange: true);

    expect($filled->fresh()->status)->toBe(BotSellOrder::STATUS_OPEN);
    expect($open->fresh()->status)->toBe(BotSellOrder::STATUS_CANCELED);
    expect($run->skipped_count)->toBe(1);
    expect((float) $this->fake->marketSells[0]['baseAmount'])->toBe(5.0);
});

it('only touches the chosen coin and keeps the order open while other coins remain', function () {
    $x = ccCurrency('XXX');
    $y = ccCurrency('YYY');
    ['order' => $order, 'sell' => $sx] = ccPosition($x, '10', '2');
    ['sell' => $sy] = ccPosition($y, '4', '5', $order);
    Cache::put('market:price:XXXUSDT', '2');

    ccStart($x);

    expect($sx->fresh()->status)->toBe(BotSellOrder::STATUS_CANCELED);
    expect($sy->fresh()->status)->toBe(BotSellOrder::STATUS_OPEN);
    expect($order->fresh()->status)->toBe('PENDING');
});

it('refuses to start while a buy of the coin is still in flight', function () {
    $x = ccCurrency('XXX');
    ['order' => $order] = ccPosition($x, '10', '2');
    BotBuyExecution::create([
        'bot_order_id'    => $order->id,
        'currency_id'     => $x->id,
        'signal_snapshot' => [],
        'allocated_usdt'  => '10',
        'status'          => BotBuyExecution::STATUS_BUYING,
    ]);

    expect(fn () => ccStart($x))->toThrow(\DomainException::class);
    expect(BotCurrencyCancellation::count())->toBe(0);
});

it('rejects selling on the exchange without canceling the tiers there first', function () {
    $x = ccCurrency('XXX');
    ccPosition($x, '10', '2');

    expect(fn () => ccStart($x, cancelOnExchange: false, sellOnExchange: true))->toThrow(\DomainException::class);
});

it('previews the per-user refund at the live price', function () {
    $x = ccCurrency('XXX');
    ccPosition($x, '10', '2');
    ccPosition($x, '5', '2');
    Cache::put('market:price:XXXUSDT', '2.5');

    $preview = app(BotCurrencyCancelService::class)->preview($x);

    expect($preview['totals']['users'])->toBe(2);
    expect($preview['totals']['sell_orders'])->toBe(2);
    expect((float) $preview['totals']['refund'])->toBe(36.0); // 24 + 12
    expect($preview['users'])->toHaveCount(2);
});
