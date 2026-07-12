<?php

namespace App\Http\Controllers\Internal;

use App\Actions\Bot\BotBuyOrchestrator;
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
use App\Models\Currency;
use App\Models\ExchangePrice;
use App\Models\Market;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Bot\ReferenceExchange\CoinExBotAdapter;
use App\Services\Bot\ReferenceExchange\ExchangeContract;
use App\Services\Bot\ReferenceExchange\FakeBotExchange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Backend for admin-panel's "Bot Test Lab" page. Lets the admin spin up
 * a full lifecycle on a single demo user without touching the real
 * reference exchange:
 *
 *   - `start`       : reset user state, fund wallet, run real orchestrator
 *   - `status`      : current snapshot for the UI
 *   - `bump-price`  : nudge a coin's spot price up/down (drives sell fills)
 *   - `set-price`   : set an absolute price
 *   - `sync`        : run `bot:sync-sell-orders` once synchronously
 *   - `reset`       : wipe everything and restore baseline
 *
 * The whole thing forces the fake exchange driver + sync queue for the
 * duration of each request so jobs execute inline (no worker needed).
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
            'user_id'      => 'required|integer|exists:users,id',
            'capital_usdt' => 'required|numeric|min:1',
            'main_balance' => 'nullable|numeric|min:0',
        ]);

        $this->forceTestMode();

        $userId  = (int) $data['user_id'];
        $capital = number_format((float) $data['capital_usdt'], self::SCALE, '.', '');
        $main    = number_format((float) ($data['main_balance'] ?? self::DEFAULT_MAIN_BALANCE), self::SCALE, '.', '');

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
        $botOrder = $orchestrator((int) $userId, BotBuyOrchestrator::TRIGGER_MANUAL);

        $reason = $botOrder ? null : $this->diagnoseOrchestratorNull($userId);

        return response()->json([
            'ok'        => true,
            'bot_order' => $botOrder?->only(['id', 'status', 'total_amount_usdt', 'triggered_by', 'created_at']),
            'reason'    => $reason,
            'snapshot'  => $this->buildStatus($userId),
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
        if (bccomp($free, (string) $global->min_deposit_usdt, 8) < 0) {
            return "free balance ({$free}) < min_deposit_usdt ({$global->min_deposit_usdt})";
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

        $this->forceTestMode();

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

        $this->forceTestMode();

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
        $this->forceTestMode();
        Artisan::call('bot:sync-sell-orders');
        return response()->json(['ok' => true, 'output' => trim(Artisan::output())]);
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'      => 'required|integer|exists:users,id',
            'main_balance' => 'nullable|numeric|min:0',
        ]);

        $this->forceTestMode();
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
            'ok'       => true,
            'snapshot' => $this->buildStatus($userId),
        ]);
    }

    /* ──────────────────────── internals ──────────────────────── */

    private function forceTestMode(): void
    {
        config(['smart-bot.exchange_driver' => 'fake']);
        config(['queue.default'       => 'sync']);
        app()->bind(ExchangeContract::class, FakeBotExchange::class);
    }

    private function writePrice(string $symbol, int $marketId, string $price): void
    {
        ExchangePrice::where('market_id', $marketId)->update(['price' => $price]);
        Cache::put("market:price:{$symbol}USDT", $price, 60 * 60 * 24);
    }

    private function wipeUser(int $userId): void
    {
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

        FakeBotExchange::purgeAll();
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
            'user_id'      => $userId,
            'main_wallet'  => $mainWallet ? ['balance' => (string) $mainWallet->balance] : null,
            'bot_wallet'   => $botWallet ? [
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
