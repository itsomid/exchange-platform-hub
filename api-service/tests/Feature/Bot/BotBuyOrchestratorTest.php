<?php

use App\Actions\Bot\BotBuyOrchestrator;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSignal;
use App\Models\Bot\BotUserSettings;
use App\Models\Bot\BotWallet;
use App\Models\Currency;
use App\Models\User;
use App\Services\Bot\PriceFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function makeBotUser(string $balance = '100.00000000'): User
{
    $user = User::create([
        'first_name' => 'Bot',
        'last_name'  => 'Trader',
        'email'      => 'bot-trader-' . uniqid() . '@example.test',
        'username'   => 'bot' . uniqid(),
        'password'   => bcrypt('secret-pass'),
        'status'     => 'ACTIVE',
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
        'auto_trade_enabled' => true,
        'reinvest_enabled'   => false,
    ]);

    return $user;
}

function makeCurrency(string $symbol): Currency
{
    return Currency::create([
        'name'             => $symbol,
        'symbol'           => $symbol,
        'price_precision'  => 2,
        'amount_precision' => 8,
    ]);
}

function makeSignal(int $currencyId, array $overrides = []): BotSignal
{
    return BotSignal::create(array_merge([
        'currency_id'             => $currencyId,
        'priority'                => 1,
        'floor_price'             => '0',
        'ceiling_price'           => '1000000',
        'min_buy_amount_usdt'     => '5',
        'max_allocation_percent'  => '100',
        'sell_orders_count'       => 0,
        'sell_mode'               => 'EQUAL',
        'sell_targets'            => [],
        'is_active'               => true,
    ], $overrides));
}

beforeEach(function () {
    // Ensure a global settings row exists so current() returns a stable instance.
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

/**
 * (1) Orchestrator persists a SKIPPED row with the expected failure_reason
 *     and locked_balance equals the sum of the NON-skipped allocations.
 */
it('persists SKIPPED executions with failure_reason and locks only non-skipped allocations', function () {
    $user = makeBotUser('100.00000000');

    $survivor = makeCurrency('AAA');
    $dropped  = makeCurrency('BBB');

    $signalA = makeSignal($survivor->id, [
        'priority'          => 1,
        'sell_orders_count' => 0,
    ]);
    $signalB = makeSignal($dropped->id, [
        'priority'          => 2,
        'sell_orders_count' => 4, // → effective_min = max(5, 4×5) = 20
    ]);

    // Both prices fall inside their signals' windows.
    $this->app->bind(PriceFeed::class, function () use ($survivor, $dropped) {
        $stub = new class extends PriceFeed {
            public array $map = [];
            public function __construct() {}
            public function getLive(int $currencyId): float
            {
                return $this->map[$currencyId] ?? 0.0;
            }
        };
        $stub->map = [$survivor->id => 50.0, $dropped->id => 50.0];
        return $stub;
    });

    /** @var BotBuyOrchestrator $orchestrator */
    $orchestrator = app(BotBuyOrchestrator::class);
    $botOrder     = $orchestrator($user->id, BotBuyOrchestrator::TRIGGER_TRANSFER_IN);

    expect($botOrder)->not->toBeNull();
    expect($botOrder->triggered_by)->toBe(BotBuyOrchestrator::TRIGGER_TRANSFER_IN);

    $allocated = BotBuyExecution::where('bot_order_id', $botOrder->id)
        ->where('status', BotBuyExecution::STATUS_PENDING)
        ->get();
    $skipped = BotBuyExecution::where('bot_order_id', $botOrder->id)
        ->where('status', BotBuyExecution::STATUS_SKIPPED)
        ->get();

    expect($allocated)->toHaveCount(1);
    expect((int) $allocated[0]->currency_id)->toBe($survivor->id);

    expect($skipped)->toHaveCount(1);
    expect((int) $skipped[0]->currency_id)->toBe($dropped->id);
    expect($skipped[0]->failure_reason)->toContain('effective_min 20');
    expect((float) $skipped[0]->allocated_usdt)->toBeLessThan(20.0);

    // locked_balance == sum of non-SKIPPED allocations.
    $wallet = BotWallet::where('user_id', $user->id)->first();
    expect((float) $wallet->locked_balance)->toBe((float) $allocated[0]->allocated_usdt);

    // Snapshot is populated.
    expect($allocated[0]->signal_snapshot)->toBeArray();
    expect($allocated[0]->signal_snapshot['bot_signal_id'])->toBe($signalA->id);
    expect($skipped[0]->signal_snapshot['bot_signal_id'])->toBe($signalB->id);

    // One job dispatched (only for the survivor).
    Queue::assertPushed(\App\Jobs\Bot\BuyExecutionJob::class, 1);
});

/**
 * (2) When all eligible coins pass D14, locked_balance equals the full
 *     non-SKIPPED sum and we dispatch exactly that many jobs.
 */
it('locks the full non-skipped allocation sum across multiple coins', function () {
    $user = makeBotUser('100.00000000');

    $c1 = makeCurrency('CCC');
    $c2 = makeCurrency('DDD');
    makeSignal($c1->id, ['priority' => 1, 'sell_orders_count' => 0]);
    makeSignal($c2->id, ['priority' => 1, 'sell_orders_count' => 0]);

    $this->app->bind(PriceFeed::class, function () use ($c1, $c2) {
        $stub = new class extends PriceFeed {
            public array $map = [];
            public function __construct() {}
            public function getLive(int $currencyId): float
            {
                return $this->map[$currencyId] ?? 0.0;
            }
        };
        $stub->map = [$c1->id => 100.0, $c2->id => 100.0];
        return $stub;
    });

    $orchestrator = app(BotBuyOrchestrator::class);
    $botOrder     = $orchestrator($user->id);

    expect($botOrder)->not->toBeNull();

    $execs = BotBuyExecution::where('bot_order_id', $botOrder->id)
        ->where('status', BotBuyExecution::STATUS_PENDING)
        ->get();
    expect($execs)->toHaveCount(2);

    $sum = '0';
    foreach ($execs as $e) {
        $sum = bcadd($sum, (string) $e->allocated_usdt, 8);
    }
    $wallet = BotWallet::where('user_id', $user->id)->first();
    expect((string) $wallet->locked_balance)->toBe($sum);

    Queue::assertPushed(\App\Jobs\Bot\BuyExecutionJob::class, 2);
});

it('persists the full balance in locked_balance when only rounding dust remains', function () {
    $user = makeBotUser('200.00000000');

    $c1 = makeCurrency('EEE');
    $c2 = makeCurrency('FFF');
    $c3 = makeCurrency('GGG');
    makeSignal($c1->id, ['priority' => 1, 'sell_orders_count' => 0]);
    makeSignal($c2->id, ['priority' => 1, 'sell_orders_count' => 0]);
    makeSignal($c3->id, ['priority' => 1, 'sell_orders_count' => 0]);

    $this->app->bind(PriceFeed::class, function () use ($c1, $c2, $c3) {
        $stub = new class extends PriceFeed {
            public array $map = [];
            public function __construct() {}
            public function getLive(int $currencyId): float
            {
                return $this->map[$currencyId] ?? 0.0;
            }
        };
        $stub->map = [$c1->id => 100.0, $c2->id => 100.0, $c3->id => 100.0];
        return $stub;
    });

    $orchestrator = app(BotBuyOrchestrator::class);
    $botOrder     = $orchestrator($user->id);

    expect($botOrder)->not->toBeNull();

    $execs = BotBuyExecution::where('bot_order_id', $botOrder->id)
        ->where('status', BotBuyExecution::STATUS_PENDING)
        ->get();
    expect($execs)->toHaveCount(3);

    $sum = '0';
    foreach ($execs as $execution) {
        $sum = bcadd($sum, (string) $execution->allocated_usdt, 8);
    }

    $wallet = BotWallet::where('user_id', $user->id)->first();
    expect($sum)->toBe('200.00000000');
    expect((string) $wallet->locked_balance)->toBe('200.00000000');

    Queue::assertPushed(\App\Jobs\Bot\BuyExecutionJob::class, 3);
});

/**
 * (3) Auto-trade OFF → no order is created and no jobs are dispatched.
 */
it('does nothing when auto_trade_enabled is false', function () {
    $user = makeBotUser('100.00000000');
    BotUserSettings::where('user_id', $user->id)->update(['auto_trade_enabled' => false]);

    $c = makeCurrency('EEE');
    makeSignal($c->id);

    $this->app->bind(PriceFeed::class, function () use ($c) {
        $stub = new class extends PriceFeed {
            public array $map = [];
            public function __construct() {}
            public function getLive(int $currencyId): float
            {
                return $this->map[$currencyId] ?? 0.0;
            }
        };
        $stub->map = [$c->id => 100.0];
        return $stub;
    });

    $orchestrator = app(BotBuyOrchestrator::class);
    $result       = $orchestrator($user->id);

    expect($result)->toBeNull();
    expect(BotOrder::count())->toBe(0);
    Queue::assertNothingPushed();
});

/**
 * After a minimum gross deposit, only the net amount (minus transfer fee)
 * lands in the bot wallet. Buy must still start at that net threshold.
 */
it('starts a buy when free balance equals min deposit net of transfer fee', function () {
    // min_deposit=20, flat fee=1 → net credited = 19
    $user = makeBotUser('19.00000000');

    $c = makeCurrency('HHH');
    makeSignal($c->id, ['priority' => 1, 'sell_orders_count' => 0, 'min_buy_amount_usdt' => '5']);

    $this->app->bind(PriceFeed::class, function () use ($c) {
        $stub = new class extends PriceFeed {
            public array $map = [];
            public function __construct() {}
            public function getLive(int $currencyId): float
            {
                return $this->map[$currencyId] ?? 0.0;
            }
        };
        $stub->map = [$c->id => 100.0];
        return $stub;
    });

    $orchestrator = app(BotBuyOrchestrator::class);
    $botOrder     = $orchestrator($user->id, BotBuyOrchestrator::TRIGGER_TRANSFER_IN);

    expect($botOrder)->not->toBeNull();
    Queue::assertPushed(\App\Jobs\Bot\BuyExecutionJob::class, 1);
});

it('does not start a buy when free balance is below min deposit net of fee', function () {
    $user = makeBotUser('18.99999999');

    $c = makeCurrency('III');
    makeSignal($c->id);

    $this->app->bind(PriceFeed::class, function () use ($c) {
        $stub = new class extends PriceFeed {
            public array $map = [];
            public function __construct() {}
            public function getLive(int $currencyId): float
            {
                return $this->map[$currencyId] ?? 0.0;
            }
        };
        $stub->map = [$c->id => 100.0];
        return $stub;
    });

    $orchestrator = app(BotBuyOrchestrator::class);
    $result       = $orchestrator($user->id);

    expect($result)->toBeNull();
    expect(BotOrder::count())->toBe(0);
    Queue::assertNothingPushed();
});
