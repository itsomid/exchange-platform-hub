<?php

use App\Actions\Bot\BotBuyOrchestrator;
use App\Models\Bot\BotBuyAttempt;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotSignal;
use App\Models\Bot\BotUserSettings;
use App\Models\Bot\BotWallet;
use App\Models\Currency;
use App\Models\User;
use App\Services\Bot\FeeCalculator;
use App\Services\Bot\PriceFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function attemptUser(string $balance, bool $autoTrade = true): User
{
    $user = User::create([
        'first_name' => 'Bot',
        'last_name'  => 'Trader',
        'email'      => 'attempt-' . uniqid() . '@example.test',
        'username'   => 'attempt' . uniqid(),
        'password'   => bcrypt('secret-pass'),
        'status'     => 'active',
    ]);

    BotWallet::create([
        'user_id'           => $user->id,
        'balance'           => $balance,
        'principal_balance' => $balance,
        'profit_balance'    => '0.00000000',
        'locked_balance'    => '0.00000000',
    ]);

    BotUserSettings::create([
        'user_id'            => $user->id,
        'auto_trade_enabled' => $autoTrade,
        'reinvest_enabled'   => false,
    ]);

    return $user;
}

function attemptCurrency(string $symbol): Currency
{
    return Currency::forceCreate([
        'name'             => $symbol,
        'persian_name'     => $symbol,
        'symbol'           => $symbol,
        'price_precision'  => 2,
        'amount_precision' => 8,
    ]);
}

/** A filled sell tier, i.e. the event that frees principal back to the wallet. */
function makeSettledSellOrder(User $user): int
{
    $currency = attemptCurrency('FRE' . random_int(100, 999));

    $order = BotOrder::create([
        'user_id'           => $user->id,
        'batch_uuid'        => (string) Str::uuid(),
        'total_amount_usdt' => '50',
        'alpha_snapshot'    => '0.15',
        'status'            => 'FILLED',
        'triggered_by'      => BotBuyOrchestrator::TRIGGER_TRANSFER_IN,
    ]);

    $execution = BotBuyExecution::create([
        'bot_order_id'    => $order->id,
        'currency_id'     => $currency->id,
        'signal_snapshot' => [],
        'allocated_usdt'  => '50',
        'filled_amount'   => '1',
        'avg_buy_price'   => '50',
        'status'          => BotBuyExecution::STATUS_BOUGHT,
    ]);

    return (int) BotSellOrder::create([
        'bot_buy_execution_id' => $execution->id,
        'target_type'          => 'percent',
        'target_value'         => '10',
        'share_percent'        => '100',
        'amount_to_sell'       => '1',
        'status'               => BotSellOrder::STATUS_FILLED,
        'filled_at'            => now(),
    ])->id;
}

function stubPrice(int $currencyId, float $price): void
{
    app()->bind(PriceFeed::class, function () use ($currencyId, $price) {
        $stub = new class extends PriceFeed
        {
            public array $map = [];

            public function __construct() {}

            public function getLive(int $currencyId): float
            {
                return $this->map[$currencyId] ?? 0.0;
            }
        };
        $stub->map = [$currencyId => $price];

        return $stub;
    });
}

beforeEach(function () {
    BotGlobalSettings::create([
        'min_deposit_usdt'          => '20',
        'alpha_weight'              => '0.15',
        'default_sell_orders_count' => 3,
        'performance_fee_percent'   => '22',
        'p2p_min_order_value'       => '5',
        'transfer_fee_tiers'        => [],
        'is_enabled'                => true,
    ]);

    Queue::fake();
});

it('records why a REINVEST bought nothing while the bot was off', function () {
    $user     = attemptUser('50.00000000', autoTrade: false);
    $sellId   = makeSettledSellOrder($user);

    $order = app(BotBuyOrchestrator::class)($user->id, BotBuyOrchestrator::TRIGGER_REINVEST, $sellId);

    expect($order)->toBeNull();

    $attempt = BotBuyAttempt::where('user_id', $user->id)->sole();

    expect($attempt->outcome)->toBe(BotBuyAttempt::OUTCOME_BLOCKED);
    expect($attempt->reason_code)->toBe('auto_trade_disabled');
    expect($attempt->triggered_by)->toBe(BotBuyOrchestrator::TRIGGER_REINVEST);
    // The freed balance stays traceable to the sell tier that released it.
    expect((int) $attempt->bot_sell_order_id)->toBe($sellId);
    expect((float) $attempt->free_balance)->toBe(50.0);
    expect($attempt->reason_message)->toContain('خاموش');
});

it('records the out-of-range signals when no signal is priced inside its window', function () {
    $user     = attemptUser('50.00000000');
    $currency = attemptCurrency('AAA');

    BotSignal::create([
        'currency_id'            => $currency->id,
        'priority'               => 1,
        'floor_price'            => '10',
        'ceiling_price'          => '20',
        'min_buy_amount_usdt'    => '5',
        'max_allocation_percent' => '100',
        'sell_orders_count'      => 0,
        'sell_mode'              => 'EQUAL',
        'sell_targets'           => [],
        'is_active'              => true,
    ]);

    stubPrice($currency->id, 99.0);

    $order = app(BotBuyOrchestrator::class)($user->id, BotBuyOrchestrator::TRIGGER_REINVEST);

    expect($order)->toBeNull();

    $attempt = BotBuyAttempt::where('user_id', $user->id)->sole();

    expect($attempt->reason_code)->toBe('no_eligible_signals');
    expect($attempt->out_of_range_count)->toBe(1);
    expect($attempt->details['out_of_range'][0]['currency_symbol'])->toBe('AAA');
    expect($attempt->details['out_of_range'][0]['floor_price'])->toBe('10.00000000');
});

it('records the free balance and the gate that a SIGNAL_SCAN failed to clear', function () {
    // Free balance under the net min deposit: exactly the case where the
    // scheduled scan can never deploy the money, because it gates on the full
    // min deposit rather than on the cheapest in-range buy floor.
    $user = attemptUser('15.00000000');

    $order = app(BotBuyOrchestrator::class)($user->id, BotBuyOrchestrator::TRIGGER_SIGNAL_SCAN);

    expect($order)->toBeNull();

    $attempt = BotBuyAttempt::where('user_id', $user->id)->sole();

    expect($attempt->reason_code)->toBe('insufficient_free_balance');
    expect($attempt->gate_kind)->toBe('min_deposit');
    expect((float) $attempt->gate_amount)->toBe((float) app(FeeCalculator::class)->minNetDeposit());
    expect((float) $attempt->free_balance)->toBe(15.0);
});

it('records a successful attempt with the pre-buy wallet snapshot', function () {
    $user     = attemptUser('100.00000000');
    $currency = attemptCurrency('AAA');

    BotSignal::create([
        'currency_id'            => $currency->id,
        'priority'               => 1,
        'floor_price'            => '0',
        'ceiling_price'          => '1000000',
        'min_buy_amount_usdt'    => '5',
        'max_allocation_percent' => '100',
        'sell_orders_count'      => 0,
        'sell_mode'              => 'EQUAL',
        'sell_targets'           => [],
        'is_active'              => true,
    ]);

    stubPrice($currency->id, 50.0);

    $order = app(BotBuyOrchestrator::class)($user->id, BotBuyOrchestrator::TRIGGER_TRANSFER_IN);

    expect($order)->not->toBeNull();

    $attempt = BotBuyAttempt::where('user_id', $user->id)->sole();

    expect($attempt->outcome)->toBe(BotBuyAttempt::OUTCOME_ORDER_CREATED);
    expect($attempt->reason_code)->toBeNull();
    expect((int) $attempt->bot_order_id)->toBe($order->id);
    expect($attempt->allocated_count)->toBe(1);
    // The snapshot is taken before the order locks the allocation, so it shows
    // the balance the decision was made on rather than the post-buy state.
    expect((float) $attempt->locked_balance)->toBe(0.0);
    expect((float) $attempt->free_balance)->toBe(100.0);
});
