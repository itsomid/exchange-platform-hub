<?php

namespace App\Http\Controllers\Internal;

use App\Actions\Bot\BotBuyOrchestrator;
use App\Events\Bot\BotAutoTradeToggled;
use App\Exceptions\Bot\BotTransferAmountTooLowException;
use App\Exceptions\Bot\InsufficientBotWalletException;
use App\Http\Controllers\Controller;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotSignal;
use App\Models\Bot\BotTradeSettlement;
use App\Models\Bot\BotUserSettings;
use App\Models\Bot\BotWallet;
use App\Models\Bot\BotWalletTransfer;
use App\Models\ExchangePrice;
use App\Models\Market;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Bot\BotWalletService;
use App\Services\Bot\FeeCalculator;
use App\Services\Bot\ReferenceExchange\CoinExBotAdapter;
use App\Services\Bot\ReferenceExchange\ExchangeContract;
use App\Services\Bot\ReferenceExchange\ExchangePositionCloser;
use App\Services\Bot\ReferenceExchange\FakeBotExchange;
use App\Services\Bot\ReferenceExchange\TestLabFailingSellExchange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Backend for admin-panel's "Bot Test Lab" page. Lets the admin spin up
 * a full lifecycle on a single demo user:
 *
 *   - `start`       : reset user state, fund wallet, trigger a buy cycle
 *   - `status`      : current snapshot for the UI
 *   - `bump-price`  : nudge a coin's spot price up/down (drives sell fills)
 *   - `set-price`   : set an absolute price
 *   - `sync`        : run `bot:sync-sell-orders` once synchronously
 *   - `reset`       : wipe everything and restore baseline
 *
 * Behaviour depends on `BOT_EXCHANGE_DRIVER` / config('smart-bot.exchange_driver'):
 *   - "fake"   → force FakeBotExchange, credit bot wallet directly, call
 *                orchestrator with TRIGGER_MANUAL (current lab harness).
 *   - "coinex" → keep the real CoinEx adapter; fund the main wallet, run a
 *                real transferIn, then toggle auto-trade ON — same path a
 *                live user takes when depositing and enabling the bot.
 *
 * Both modes force the sync queue for the duration of each request so jobs
 * execute inline (no worker needed for the lab itself).
 *
 * Protected by `bot-test-auth` middleware (shared X-Internal-Token header).
 */
class BotTestController extends Controller
{
    private const FEE_TYPE = 'bot';
    private const DEFAULT_MAIN_BALANCE = 500;
    private const SCALE = 8;

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'                     => 'required|integer|exists:users,id',
            'capital_usdt'                => 'required|numeric|min:1',
            'main_balance'                => 'nullable|numeric|min:0',
            'simulate_sell_place_failure' => 'sometimes|boolean',
        ]);

        $simulateSellFailure = (bool) ($data['simulate_sell_place_failure'] ?? false);
        $this->prepareLabRuntime($simulateSellFailure);

        $userId  = (int) $data['user_id'];
        $capital = number_format((float) $data['capital_usdt'], self::SCALE, '.', '');
        $mainDefault = max(self::DEFAULT_MAIN_BALANCE, (float) $data['capital_usdt']);
        $main = number_format((float) ($data['main_balance'] ?? $mainDefault), self::SCALE, '.', '');

        if ($this->isFakeDriver()) {
            return $this->startFake($userId, $capital, $main, $simulateSellFailure);
        }

        return $this->startLive($userId, $capital, $main, $simulateSellFailure);
    }

    /**
     * Fake-driver path: credit bot wallet directly and invoke orchestrator.
     */
    private function startFake(int $userId, string $capital, string $main, bool $simulateSellFailure = false): JsonResponse
    {
        $this->wipeUser($userId);

        DB::transaction(function () use ($userId, $main, $capital) {
            BotUserSettings::updateOrCreate(
                ['user_id' => $userId],
                ['auto_trade_enabled' => true, 'reinvest_enabled' => false, 'terms_accepted_at' => now()],
            );

            Wallet::updateOrCreate(
                ['user_id' => $userId, 'currency_symbol' => 'USDT'],
                ['balance' => $main, 'locked_balance' => 0],
            );

            BotWallet::updateOrCreate(
                ['user_id' => $userId],
                [
                    'balance'           => $capital,
                    'principal_balance' => 0,
                    'profit_balance'    => 0,
                    'locked_balance'    => 0,
                ],
            );
        });

        /** @var BotBuyOrchestrator $orchestrator */
        $orchestrator = app(BotBuyOrchestrator::class);
        $botOrder = $orchestrator($userId, BotBuyOrchestrator::TRIGGER_MANUAL);

        $reason = $botOrder ? null : $this->diagnoseOrchestratorNull($userId);

        return response()->json([
            'ok'                          => true,
            'exchange_driver'             => $this->exchangeDriver(),
            'simulate_sell_place_failure' => $simulateSellFailure,
            'bot_order'                   => $botOrder?->only(['id', 'status', 'total_amount_usdt', 'triggered_by', 'created_at']),
            'reason'                      => $reason,
            'snapshot'                    => $this->buildStatus($userId),
        ]);
    }

    /**
     * Live (CoinEx) path: mirror a real user — fund main wallet, transferIn
     * to bot wallet, then flip auto_trade OFF→ON so HandleBotAutoTradeToggled
     * runs the orchestrator with TRIGGER_TOGGLE_ON against the real exchange.
     */
    private function startLive(int $userId, string $capital, string $main, bool $simulateSellFailure = false): JsonResponse
    {
        if (bccomp($main, $capital, self::SCALE) < 0) {
            return response()->json([
                'ok'    => false,
                'error' => "موجودی کیف اصلی ({$main}) باید حداقل برابر سرمایه واریزی ({$capital}) باشد.",
            ], 422);
        }

        $this->wipeUser($userId);

        $user = User::findOrFail($userId);

        DB::transaction(function () use ($userId, $main) {
            BotUserSettings::updateOrCreate(
                ['user_id' => $userId],
                ['auto_trade_enabled' => false, 'reinvest_enabled' => false, 'terms_accepted_at' => now()],
            );

            Wallet::updateOrCreate(
                ['user_id' => $userId, 'currency_symbol' => 'USDT'],
                ['balance' => $main, 'locked_balance' => 0],
            );

            BotWallet::updateOrCreate(
                ['user_id' => $userId],
                [
                    'balance'           => 0,
                    'principal_balance' => 0,
                    'profit_balance'    => 0,
                    'locked_balance'    => 0,
                ],
            );
        });

        try {
            app(BotWalletService::class)->transferIn($user, $capital);
        } catch (BotTransferAmountTooLowException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage() ?: 'مبلغ واریز کمتر از حداقل مجاز است.'], 422);
        } catch (InsufficientBotWalletException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage() ?: 'موجودی کیف اصلی کافی نیست.'], 422);
        }

        $settings = BotUserSettings::where('user_id', $userId)->firstOrFail();
        $settings->auto_trade_enabled = true;
        $settings->save();
        event(new BotAutoTradeToggled($userId, true));

        $botOrder = BotOrder::where('user_id', $userId)->orderByDesc('id')->first();
        $reason = $botOrder ? null : $this->diagnoseOrchestratorNull($userId);

        return response()->json([
            'ok'                          => true,
            'exchange_driver'             => $this->exchangeDriver(),
            'simulate_sell_place_failure' => $simulateSellFailure,
            'bot_order'                   => $botOrder?->only(['id', 'status', 'total_amount_usdt', 'triggered_by', 'created_at']),
            'reason'                      => $reason,
            'snapshot'                    => $this->buildStatus($userId),
        ]);
    }

    private function diagnoseOrchestratorNull(int $userId): string
    {
        $settings = BotUserSettings::where('user_id', $userId)->first();
        if (! $settings || ! $settings->auto_trade_enabled) return 'auto_trade_enabled = false';

        $global = BotGlobalSettings::current();
        if (! $global->is_enabled) return 'BotGlobalSettings.is_enabled = false';

        $wallet = BotWallet::where('user_id', $userId)->first();
        if (! $wallet) return 'BotWallet missing';

        $free = bcsub((string) $wallet->balance, (string) $wallet->locked_balance, 8);
        $minNet = app(FeeCalculator::class)->minNetDeposit();
        if (bccomp($free, $minNet, 8) < 0) {
            return "free balance ({$free}) < min_net_deposit ({$minNet}) [min_deposit_usdt={$global->min_deposit_usdt}]";
        }

        $activeCount = BotSignal::where('is_active', true)->count();
        if ($activeCount === 0) return 'no active BotSignal rows';

        return "active signals=$activeCount but none eligible (price outside floor/ceiling, or no live price). check PriceFeed cache `market:price:{SYMBOL}USDT`.";
    }

    public function status(Request $request): JsonResponse
    {
        $userId = (int) $request->validate(['user_id' => 'required|integer'])['user_id'];
        return response()->json($this->buildStatus($userId));
    }

    public function bumpPrice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'symbol'    => 'required|string',
            'direction' => 'required|in:up,down',
            'step'      => 'required|numeric|min:0.0001',
            'mode'      => 'nullable|in:percent,absolute',
        ]);

        $this->prepareLabRuntime();

        $symbol = strtoupper((string) $data['symbol']);
        $mode   = $data['mode'] ?? 'percent';
        $sign   = $data['direction'] === 'up' ? 1 : -1;

        $market = Market::where('base_currency', $symbol)->where('quote_currency', 'USDT')->with('exchangePrice')->first();
        if (! $market || ! $market->exchangePrice) {
            return response()->json(['ok' => false, 'error' => "No USDT market for {$symbol}"], 422);
        }

        $current = (string) $market->exchangePrice->price;
        if ($mode === 'percent') {
            $factor  = bcadd('1', bcdiv((string) ($sign * (float) $data['step']), '100', 10), 10);
            $newPrice = bcmul($current, $factor, self::SCALE);
        } else {
            $delta    = bcmul((string) $sign, (string) $data['step'], self::SCALE);
            $newPrice = bcadd($current, $delta, self::SCALE);
        }
        if (bccomp($newPrice, '0', self::SCALE) <= 0) {
            $newPrice = '0.00000001';
        }

        $this->writePrice($symbol, $market->id, $newPrice);

        // Drive any pending sell fills right away.
        Artisan::call('bot:sync-sell-orders');

        return response()->json([
            'ok'        => true,
            'symbol'    => $symbol,
            'old_price' => $current,
            'new_price' => $newPrice,
        ]);
    }

    public function setPrice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'symbol' => 'required|string',
            'price'  => 'required|numeric|min:0.00000001',
        ]);

        $this->prepareLabRuntime();

        $symbol = strtoupper((string) $data['symbol']);
        $market = Market::where('base_currency', $symbol)->where('quote_currency', 'USDT')->first();
        if (! $market) {
            return response()->json(['ok' => false, 'error' => "No USDT market for {$symbol}"], 422);
        }

        $price = number_format((float) $data['price'], self::SCALE, '.', '');
        $this->writePrice($symbol, $market->id, $price);
        Artisan::call('bot:sync-sell-orders');

        return response()->json(['ok' => true, 'symbol' => $symbol, 'new_price' => $price]);
    }

    public function sync(Request $request): JsonResponse
    {
        $this->prepareLabRuntime();
        Artisan::call('bot:sync-sell-orders');
        return response()->json([
            'ok'              => true,
            'exchange_driver' => $this->exchangeDriver(),
            'output'          => trim(Artisan::output()),
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'      => 'required|integer|exists:users,id',
            'main_balance' => 'nullable|numeric|min:0',
        ]);

        $this->prepareLabRuntime();
        $userId = (int) $data['user_id'];
        $main   = number_format((float) ($data['main_balance'] ?? self::DEFAULT_MAIN_BALANCE), self::SCALE, '.', '');

        $this->wipeUser($userId);

        DB::transaction(function () use ($userId, $main) {
            BotUserSettings::updateOrCreate(
                ['user_id' => $userId],
                ['auto_trade_enabled' => false],
            );

            Wallet::updateOrCreate(
                ['user_id' => $userId, 'currency_symbol' => 'USDT'],
                ['balance' => $main, 'locked_balance' => 0],
            );
            BotWallet::updateOrCreate(
                ['user_id' => $userId],
                ['balance' => 0, 'principal_balance' => 0, 'profit_balance' => 0, 'locked_balance' => 0],
            );
        });

        return response()->json([
            'ok'              => true,
            'exchange_driver' => $this->exchangeDriver(),
            'snapshot'        => $this->buildStatus($userId),
        ]);
    }

    /* ──────────────────────── internals ──────────────────────── */

    private function exchangeDriver(): string
    {
        return (string) config('smart-bot.exchange_driver', 'coinex');
    }

    private function isFakeDriver(): bool
    {
        return $this->exchangeDriver() === 'fake';
    }

    /**
     * Always run lab jobs inline. Bind FakeBotExchange when the configured
     * driver is fake. Optionally wrap the exchange so the 2nd placeLimitSell
     * fails with a simulated curl/SSL transport error (Test Lab switch).
     *
     * The wrapper is bound as a container instance so the call counter survives
     * BuyExecutionJob → OpenSellOrdersJob within the same sync request.
     */
    private function prepareLabRuntime(bool $simulateSellPlaceFailure = false): void
    {
        config(['queue.default' => 'sync']);

        if ($this->isFakeDriver()) {
            config(['smart-bot.exchange_driver' => 'fake']);
        }

        if (! $simulateSellPlaceFailure) {
            if ($this->isFakeDriver()) {
                app()->bind(ExchangeContract::class, FakeBotExchange::class);
            }

            return;
        }

        $inner = $this->isFakeDriver()
            ? new FakeBotExchange()
            : new CoinExBotAdapter();

        // Fail the last placeLimitSell in each OpenSellOrdersJob batch (armed
        // via expectLimitSells) so earlier tiers place, then rollback + markFailed.
        app()->instance(
            ExchangeContract::class,
            new TestLabFailingSellExchange($inner),
        );
    }

    private function writePrice(string $symbol, int $marketId, string $price): void
    {
        ExchangePrice::where('market_id', $marketId)->update(['price' => $price]);
        Cache::put("market:price:{$symbol}USDT", $price, 60 * 60 * 24);
    }

    private function wipeUser(int $userId): void
    {
        $this->unwindExchangePositions($userId);

        DB::transaction(function () use ($userId) {
            $orderIds = BotOrder::where('user_id', $userId)->pluck('id')->all();
            $execIds  = BotBuyExecution::whereIn('bot_order_id', $orderIds)->pluck('id')->all();

            BotTradeSettlement::where('user_id', $userId)->delete();
            if (! empty($execIds)) {
                BotSellOrder::whereIn('bot_buy_execution_id', $execIds)->delete();
            }
            BotBuyExecution::whereIn('bot_order_id', $orderIds)->delete();
            BotOrder::where('user_id', $userId)->delete();

            $transferIds = BotWalletTransfer::where('user_id', $userId)->pluck('id')->all();
            if (! empty($transferIds)) {
                Transaction::whereIn('bot_wallet_transfer_id', $transferIds)->delete();
                BotWalletTransfer::where('user_id', $userId)->delete();
            }

            Transaction::where('user_id', $userId)
                ->where('type', self::FEE_TYPE)
                ->delete();
        });

        if ($this->isFakeDriver()) {
            FakeBotExchange::purgeAll();
        }
    }

    /**
     * On live CoinEx lab reset: cancel OPEN limit sells, then market-sell the
     * residual base inventory so the omnibus account is not left holding coins
     * from the cancelled buy cycle. Market buys are already filled (nothing to
     * "cancel"); the leftover is the bought base position.
     *
     * When the buy fee was charged in base, available balance may be lower than
     * a gross amount — {@see ExchangePositionCloser} retries with fee subtracted.
     */
    private function unwindExchangePositions(int $userId): void
    {
        if ($this->isFakeDriver()) {
            return;
        }

        $executions = BotBuyExecution::query()
            ->where('status', BotBuyExecution::STATUS_BOUGHT)
            ->whereHas('botOrder', fn ($q) => $q->where('user_id', $userId))
            ->with(['currency', 'sellOrders'])
            ->get();

        if ($executions->isEmpty()) {
            return;
        }

        $closer = app(ExchangePositionCloser::class);

        foreach ($executions as $execution) {
            $symbol = $execution->currency?->symbol;
            if (! $symbol) {
                continue;
            }

            $market = strtoupper($symbol).'USDT';
            $exchange = app(ExchangeContract::class);

            foreach ($execution->sellOrders as $sell) {
                if ($sell->status !== BotSellOrder::STATUS_OPEN || ! $sell->exchange_order_id) {
                    continue;
                }
                try {
                    $exchange->cancelOrder($market, (string) $sell->exchange_order_id);
                } catch (Throwable $e) {
                    Log::channel('smart-bot')->warning('bot.test-lab.cancel_open_sell_failed', [
                        'user_id'           => $userId,
                        'sell_order_id'     => $sell->id,
                        'market'            => $market,
                        'exchange_order_id' => $sell->exchange_order_id,
                        'error'             => $e->getMessage(),
                    ]);
                }
            }

            $amount = $this->residualBaseAmount($execution);
            if (bccomp($amount, '0', self::SCALE) <= 0) {
                continue;
            }

            $baseFee = ExchangePositionCloser::baseFeeCoinFromExecution($execution);
            $result  = $closer->marketSell($market, $amount, $baseFee);

            if ($result->exchangeOrderId === null) {
                Log::channel('smart-bot')->warning('bot.test-lab.liquidate_failed', [
                    'user_id'      => $userId,
                    'execution_id' => $execution->id,
                    'market'       => $market,
                    'amount'       => $amount,
                    'base_fee'     => $baseFee,
                    'error_code'   => $result->errorCode,
                    'error'        => $result->errorMessage,
                ]);
            } else {
                Log::channel('smart-bot')->info('bot.test-lab.liquidated', [
                    'user_id'           => $userId,
                    'execution_id'      => $execution->id,
                    'market'            => $market,
                    'amount'            => $amount,
                    'exchange_order_id' => $result->exchangeOrderId,
                    'filled'            => $result->filledAmount,
                ]);
            }
        }
    }

    /**
     * Base coin still held for a BOUGHT execution: OPEN tier sizes after cancel,
     * or filled_amount minus already-FILLED tiers when no open sells remain.
     */
    private function residualBaseAmount(BotBuyExecution $execution): string
    {
        $openSum    = '0';
        $filledSold = '0';

        foreach ($execution->sellOrders as $sell) {
            $amt = (string) $sell->amount_to_sell;
            if ($sell->status === BotSellOrder::STATUS_OPEN) {
                $openSum = bcadd($openSum, $amt, self::SCALE);
            } elseif ($sell->status === BotSellOrder::STATUS_FILLED) {
                $filledSold = bcadd($filledSold, $amt, self::SCALE);
            }
        }

        if (bccomp($openSum, '0', self::SCALE) > 0) {
            return $openSum;
        }

        $residual = bcsub((string) $execution->filled_amount, $filledSold, self::SCALE);

        return bccomp($residual, '0', self::SCALE) > 0 ? $residual : '0';
    }

    private function buildStatus(int $userId): array
    {
        $user        = User::find($userId);
        $mainWallet  = $user ? Wallet::where('user_id', $userId)->where('currency_symbol', 'USDT')->first() : null;
        $botWallet   = BotWallet::where('user_id', $userId)->first();
        $settings    = BotUserSettings::where('user_id', $userId)->first();

        $orders = BotOrder::where('user_id', $userId)
            ->with(['buyExecutions.currency', 'buyExecutions.sellOrders'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $executions = BotBuyExecution::whereIn('bot_order_id', $orders->pluck('id'))
            ->with(['currency', 'sellOrders'])
            ->get()
            ->map(function (BotBuyExecution $e) {
                return [
                    'id'             => $e->id,
                    'currency'       => $e->currency?->symbol,
                    'status'         => $e->status,
                    'allocated_usdt' => (string) $e->allocated_usdt,
                    'filled_amount'  => (string) $e->filled_amount,
                    'avg_buy_price'  => (string) $e->avg_buy_price,
                    'failure_reason' => $e->failure_reason,
                    'sell_orders'    => $e->sellOrders->map(fn ($s) => [
                        'id'             => $s->id,
                        'status'         => $s->status,
                        'target_type'    => $s->target_type,
                        'target_value'   => (string) $s->target_value,
                        'limit_price'    => $this->resolvePrice($s->target_type, (string) $s->target_value, (string) $e->avg_buy_price),
                        'share_percent'  => (string) $s->share_percent,
                        'amount_to_sell' => (string) $s->amount_to_sell,
                    ]),
                ];
            });

        $signals = BotSignal::with('currency')->where('is_active', true)->orderBy('priority')->get()
            ->map(function (BotSignal $s) {
                $sym = $s->currency?->symbol;
                $market = $sym
                    ? Market::where('base_currency', $sym)->where('quote_currency', 'USDT')->with('exchangePrice')->first()
                    : null;
                return [
                    'currency_id'   => $s->currency_id,
                    'symbol'        => $sym,
                    'priority'      => $s->priority,
                    'floor_price'   => (string) $s->floor_price,
                    'ceiling_price' => (string) $s->ceiling_price,
                    'current_price' => $market?->exchangePrice ? (string) $market->exchangePrice->price : null,
                    'is_eligible'   => $this->priceEligible($market?->exchangePrice?->price, $s->floor_price, $s->ceiling_price),
                ];
            });

        $settlements = BotTradeSettlement::where('user_id', $userId)
            ->orderByDesc('id')->limit(20)
            ->get()
            ->map(fn ($t) => [
                'id'              => $t->id,
                'gross_revenue'   => (string) $t->gross_revenue,
                'cost_basis'      => (string) $t->cost_basis,
                'performance_fee' => (string) $t->performance_fee,
                'cancel_fee'      => (string) $t->cancel_fee,
                'net_pnl'         => (string) $t->net_pnl,
                'settled_at'      => $t->settled_at,
            ]);

        $transactions = Transaction::where('user_id', $userId)
            ->where('type', self::FEE_TYPE)
            ->orderByDesc('id')->limit(30)
            ->get(['id', 'subtype', 'amount', 'balance', 'description', 'created_at']);

        return [
            'exchange_driver' => $this->exchangeDriver(),
            'user_id'         => $userId,
            'main_wallet'     => $mainWallet ? ['balance' => (string) $mainWallet->balance] : null,
            'bot_wallet'      => $botWallet ? [
                'balance'        => (string) $botWallet->balance,
                'locked_balance' => (string) $botWallet->locked_balance,
                'profit_balance' => (string) $botWallet->profit_balance,
            ] : null,
            'settings'     => $settings ? [
                'auto_trade_enabled' => (bool) $settings->auto_trade_enabled,
            ] : null,
            'orders'       => $orders->map(fn ($o) => [
                'id'                => $o->id,
                'status'            => $o->status,
                'description'       => $o->description,
                'admin_description' => $o->admin_description,
                'total_amount_usdt' => (string) $o->total_amount_usdt,
                'triggered_by'      => $o->triggered_by,
                'created_at'        => $o->created_at,
            ]),
            'executions'   => $executions,
            'signals'      => $signals,
            'settlements'  => $settlements,
            'transactions' => $transactions,
        ];
    }

    private function priceEligible($price, $floor, $ceiling): bool
    {
        if ($price === null) return false;
        return (float) $price >= (float) $floor && (float) $price <= (float) $ceiling;
    }

    private function resolvePrice(string $type, string $trigger, ?string $avgBuyPrice): ?string
    {
        if (! $avgBuyPrice || (float) $avgBuyPrice <= 0) return null;
        if ($type === 'price') return $trigger;
        $factor = bcadd('1', bcdiv($trigger, '100', 10), 10);
        return bcmul($avgBuyPrice, $factor, self::SCALE);
    }
}
