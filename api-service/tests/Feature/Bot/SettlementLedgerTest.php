<?php

use App\Enums\TransactionSubTypeEnum;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotWallet;
use App\Models\Currency;
use App\Models\ReferralCode;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Bot\SettlementService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Production has transactions.coin_price, but it was added outside the
    // migrations the test schema is built from.
    if (! Schema::hasColumn('transactions', 'coin_price')) {
        Schema::table('transactions', fn (Blueprint $t) => $t->decimal('coin_price', 20, 8)->nullable());
    }

    BotGlobalSettings::create([
        'min_deposit_usdt'          => 20,
        'alpha_weight'              => 0.15,
        'default_sell_orders_count' => 3,
        'performance_fee_percent'   => 50,
        'referral_fee_percent'      => 10,
        'p2p_min_order_value'       => 5,
        'transfer_fee_tiers'        => [],
        'is_enabled'                => true,
    ]);

    $this->exchangeUser = User::factory()->create();
    config(['bitexroom.user_id' => $this->exchangeUser->id]);
    $this->exchangeUsdt = Wallet::create([
        'user_id'         => $this->exchangeUser->id,
        'currency_symbol' => 'USDT',
        'balance'         => '1000.00000000',
        'locked_balance'  => '0.00000000',
    ]);
});

/** 10 coins bought @ 100 (cost 1000), one OPEN tier selling all of them. */
function ledgerTier(string $buyFeeUsdt = '0', string $buyFeeCurrency = 'USDT', ?User $user = null): BotSellOrder
{
    $user ??= User::factory()->create();
    $currency = Currency::forceCreate(['name' => 'TST', 'persian_name' => 'TST', 'symbol' => 'TST']);

    BotWallet::create([
        'user_id'           => $user->id,
        'balance'           => '1000',
        'principal_balance' => '1000',
        'profit_balance'    => '0',
        'locked_balance'    => '1000',
    ]);
    Wallet::create(['user_id' => $user->id, 'currency_symbol' => 'USDT', 'balance' => '0', 'locked_balance' => '0']);

    $order = BotOrder::create([
        'user_id'           => $user->id,
        'batch_uuid'        => (string) \Illuminate\Support\Str::uuid(),
        'total_amount_usdt' => '1000',
        'alpha_snapshot'    => 0.15,
        'status'            => 'PENDING',
        'triggered_by'      => 'MANUAL',
    ]);
    $execution = BotBuyExecution::create([
        'bot_order_id'                  => $order->id,
        'currency_id'                   => $currency->id,
        'signal_snapshot'               => [],
        'allocated_usdt'                => '1000',
        'filled_amount'                 => '10',
        'avg_buy_price'                 => '100',
        'buy_ref_exchange_fee'          => $buyFeeUsdt,
        'buy_ref_exchange_fee_currency' => $buyFeeCurrency,
        'network_fee'                   => 0,
        'status'                        => BotBuyExecution::STATUS_BOUGHT,
    ]);

    return BotSellOrder::create([
        'bot_buy_execution_id' => $execution->id,
        'target_type'          => 'percent',
        'target_value'         => 20,
        'share_percent'        => 100,
        'amount_to_sell'       => '10',
        'status'               => BotSellOrder::STATUS_OPEN,
    ]);
}

it('credits the network fee to the Bitexroom wallet, never to the trader', function () {
    $sell   = ledgerTier();
    $trader = $sell->botBuyExecution->botOrder->user_id;

    // sold @ 110 → gross pnl 100; network 4 → pnl after fees 96; perf 50% = 48
    app(SettlementService::class)->settleCancelMarketSell($sell, '10', '110', '4', '0', '50', BotSellOrder::CANCEL_ADMIN);

    $network = Transaction::where('subtype', TransactionSubTypeEnum::BOT_NETWORK_FEE)->sole();
    expect($network->user_id)->toBe($this->exchangeUser->id)
        ->and($network->wallet_id)->toBe($this->exchangeUsdt->id)
        ->and((float) $network->amount)->toBe(4.0);

    expect(Transaction::where('user_id', $trader)->count())->toBe(0);
    expect((float) $this->exchangeUsdt->fresh()->balance)->toBe(1000.0 + 4.0 + 48.0);
});

it('funds the referral commission from the Bitexroom wallet', function () {
    $introducer = User::factory()->create();
    $introducerWallet = Wallet::create(['user_id' => $introducer->id, 'currency_symbol' => 'USDT', 'balance' => '0', 'locked_balance' => '0']);
    $code = ReferralCode::create(['code' => 'INTRO1', 'user_id' => $introducer->id, 'introducer_fee' => 0, 'friend_fee' => 0]);
    $trader = User::factory()->create();
    $trader->forceFill(['introducer_code' => $code->id])->save();

    // sold @ 120 → pnl 200; perf 50% = 100; referral 10% of 200 = 20
    app(SettlementService::class)->settleFill(ledgerTier(user: $trader), '10', '120');

    expect((float) $introducerWallet->fresh()->balance)->toBe(20.0);
    expect((float) $this->exchangeUsdt->fresh()->balance)->toBe(1000.0 + 100.0 - 20.0);

    $expense = Transaction::where('subtype', TransactionSubTypeEnum::BOT_REFERRAL_COMMISSION)
        ->where('user_id', $this->exchangeUser->id)
        ->sole();
    expect((float) $expense->amount)->toBe(-20.0)
        ->and($expense->wallet_id)->toBe($this->exchangeUsdt->id);
});

it('ledgers a base-coin buy fee in the coin and the sell fee in USDT', function () {
    // buy fee 2 USDT paid in TST (10 coins → full share on this tier)
    $sell = ledgerTier('2', 'TST');

    app(SettlementService::class)->settleFill($sell, '10', '120', '0', '5');

    $fees = Transaction::where('subtype', TransactionSubTypeEnum::BOT_EXCHANGE_FEE)
        ->with('wallet')
        ->get()
        ->keyBy(fn ($t) => $t->wallet->currency_symbol);

    expect($fees)->toHaveCount(2);
    expect((float) $fees['TST']->amount)->toBe(-0.02)
        ->and((float) $fees['TST']->coin_price)->toBe(100.0);
    expect((float) $fees['USDT']->amount)->toBe(-5.0)
        ->and($fees['USDT']->user_id)->toBe($this->exchangeUser->id);
});

it('ledgers the buy fee share when an admin coin cancel settles a tier', function () {
    $sell = ledgerTier('3', 'USDT');

    app(SettlementService::class)->settleCurrencyCancel($sell, '100', '50', null);

    $fee = Transaction::where('subtype', TransactionSubTypeEnum::BOT_EXCHANGE_FEE)->sole();
    expect((float) $fee->amount)->toBe(-3.0)
        ->and($fee->user_id)->toBe($this->exchangeUser->id);
});
