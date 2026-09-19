<?php

use App\Actions\Bot\BotBuyOrchestrator;
use App\Jobs\Bot\SignalScanBuyJob;
use App\Models\Bot\BotBuyAttempt;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotSignal;
use App\Models\Bot\BotUserSettings;
use App\Models\Bot\BotWallet;
use App\Models\Currency;
use App\Models\User;
use App\Services\Bot\PriceFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function scanUser(string $balance, string $locked = '0.00000000', bool $autoTrade = true): User
{
    $user = User::create([
        'first_name' => 'Scan',
        'last_name'  => 'Trader',
        'email'      => 'scan-' . uniqid() . '@example.test',
        'username'   => 'scan' . uniqid(),
        'password'   => bcrypt('secret-pass'),
        'status'     => 'active',
    ]);

    BotWallet::create([
        'user_id'           => $user->id,
        'balance'           => $balance,
        'principal_balance' => $balance,
        'profit_balance'    => '0.00000000',
        'locked_balance'    => $locked,
    ]);

    BotUserSettings::create([
        'user_id'            => $user->id,
        'auto_trade_enabled' => $autoTrade,
        'reinvest_enabled'   => false,
    ]);

    return $user;
}

function scanSignal(bool $priceInRange): BotSignal
{
    $currency = Currency::forceCreate([
        'name'             => 'SCN',
        'persian_name'     => 'SCN',
        'symbol'           => 'SCN' . random_int(1000, 9999),
        'price_precision'  => 2,
        'amount_precision' => 8,
    ]);

    return BotSignal::create([
        'currency_id'                  => $currency->id,
        'priority'                     => 1,
        'floor_price'                  => '1.00000000',
        'ceiling_price'                => '2.00000000',
        'min_buy_amount_usdt'          => '2.70000000',
        'max_allocation_percent'       => '20.00',
        'sell_orders_count'            => 4,
        'p2p_min_order_value_override' => '2.70000000',
        'sell_mode'                    => 'percent',
        'sell_targets'                 => [],
        'is_active'                    => true,
        'price_in_range'               => $priceInRange,
    ]);
}

function scanStubPrice(int $currencyId, float $price): void
{
    app()->bind(PriceFeed::class, function () use ($currencyId, $price) {
        $stub = new class extends PriceFeed
        {
            public array $map = [];

            public function __construct() {}

            public function getLive(int $currencyId): float
            {
                if (! isset($this->map[$currencyId])) {
                    throw new RuntimeException('no live price');
                }

                return $this->map[$currencyId];
            }
        };
        $stub->map = [$currencyId => $price];

        return $stub;
    });
}

function recordScanAttempt(User $user, string $freeBalance): void
{
    BotBuyAttempt::create([
        'user_id'      => $user->id,
        'triggered_by' => BotBuyOrchestrator::TRIGGER_SIGNAL_SCAN,
        'outcome'      => BotBuyAttempt::OUTCOME_BLOCKED,
        'reason_code'  => 'no_buyable_allocation',
        'free_balance' => $freeBalance,
    ]);
}

beforeEach(function () {
    // Mirrors production: 30 USDT min deposit (→ 29 net of the 1 USDT transfer
    // fee tier) against a 2.7 USDT cheapest buy floor in 'single' floor mode.
    BotGlobalSettings::create([
        'min_deposit_usdt'          => '30',
        'alpha_weight'              => '0.15',
        'default_sell_orders_count' => 4,
        'performance_fee_percent'   => '22',
        'p2p_min_order_value'       => '5',
        'precheck_floor_mode'       => 'single',
        'transfer_fee_tiers'        => [],
        'is_enabled'                => true,
    ]);

    Queue::fake();
});

it('dispatches a user whose free balance clears the buy floor but not the min deposit', function () {
    // Exactly the stranded case: 10 USDT free is far below the 29 USDT net min
    // deposit the scan used to require, but well above the 2.7 USDT that can
    // actually buy the in-range signal.
    $user   = scanUser('10.00000000');
    $signal = scanSignal(priceInRange: false);

    scanStubPrice($signal->currency_id, 1.5);

    $this->artisan('bot:scan-buy-triggers')->assertSuccessful();

    Queue::assertPushed(SignalScanBuyJob::class, 1);
    expect($signal->fresh()->price_in_range)->toBeTrue();
});

it('dispatches a user whose balance moved even when no signal moved', function () {
    $user   = scanUser('10.00000000');
    $signal = scanSignal(priceInRange: true);

    // The scan last evaluated this user at 5 USDT; a sell tier has since freed
    // more, so there is new money to try even though no signal transitioned.
    recordScanAttempt($user, '5.00000000');
    scanStubPrice($signal->currency_id, 1.5);

    $this->artisan('bot:scan-buy-triggers')->assertSuccessful();

    Queue::assertPushed(SignalScanBuyJob::class, 1);
});

it('does not re-dispatch when neither the balance nor any signal moved', function () {
    $user   = scanUser('10.00000000');
    $signal = scanSignal(priceInRange: true);

    recordScanAttempt($user, '10.00000000');
    scanStubPrice($signal->currency_id, 1.5);

    $this->artisan('bot:scan-buy-triggers')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('does not dispatch anyone while no signal is inside its window', function () {
    scanUser('500.00000000');
    $signal = scanSignal(priceInRange: false);

    scanStubPrice($signal->currency_id, 99.0);

    $this->artisan('bot:scan-buy-triggers')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('skips users whose free balance is below the cheapest buy floor', function () {
    scanUser('1.00000000');
    $signal = scanSignal(priceInRange: false);

    scanStubPrice($signal->currency_id, 1.5);

    $this->artisan('bot:scan-buy-triggers')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('skips users whose bot is switched off', function () {
    scanUser('10.00000000', autoTrade: false);
    $signal = scanSignal(priceInRange: false);

    scanStubPrice($signal->currency_id, 1.5);

    $this->artisan('bot:scan-buy-triggers')->assertSuccessful();

    Queue::assertNothingPushed();
});
