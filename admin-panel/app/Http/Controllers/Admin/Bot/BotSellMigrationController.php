<?php

namespace App\Http\Controllers\Admin\Bot;

use App\Http\Controllers\Controller;
use App\Models\Bot\BotSellMigration;
use App\Models\Bot\BotSellOrder;
use App\Models\Currency;
use App\Services\Exchanges\Asset\AssetFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * "Move sell tiers to Binance" page: per coin, how much coin the OPEN tiers
 * still pointing at CoinEx need versus the free Binance balance, plus the
 * history of migration runs. The run itself is proxied to api-service
 * (BotOrderController::sellMigration*).
 */
class BotSellMigrationController extends Controller
{
    public function index(): View
    {
        $tiers = BotSellOrder::query()
            ->join('bot_buy_executions', 'bot_buy_executions.id', '=', 'bot_sell_orders.bot_buy_execution_id')
            ->join('bot_orders', 'bot_orders.id', '=', 'bot_buy_executions.bot_order_id')
            ->where('bot_sell_orders.status', 'OPEN')
            ->where('bot_sell_orders.exchange', BotSellOrder::EXCHANGE_COINEX)
            ->get([
                'bot_buy_executions.currency_id',
                'bot_orders.user_id',
                'bot_buy_executions.bot_order_id',
                'bot_buy_executions.avg_buy_price',
                'bot_sell_orders.target_type',
                'bot_sell_orders.target_value',
                'bot_sell_orders.amount_to_sell',
            ])
            ->groupBy('currency_id');

        $tierStats = BotSellOrder::query()
            ->join('bot_buy_executions', 'bot_buy_executions.id', '=', 'bot_sell_orders.bot_buy_execution_id')
            ->groupBy('bot_buy_executions.currency_id')
            ->selectRaw('bot_buy_executions.currency_id')
            ->selectRaw("SUM(CASE WHEN bot_sell_orders.status = 'FILLED' THEN 1 ELSE 0 END) as filled_tiers")
            ->selectRaw('SUM(CASE WHEN bot_sell_orders.migrated_at IS NOT NULL THEN 1 ELSE 0 END) as migrated_tiers')
            ->get()
            ->keyBy('currency_id');

        [$balances, $balanceError] = $this->binanceBalances();

        $running = BotSellMigration::query()
            ->whereIn('status', [BotSellMigration::STATUS_PENDING, BotSellMigration::STATUS_RUNNING])
            ->get()
            ->reject(fn (BotSellMigration $m) => $m->failIfOrphaned())
            ->keyBy('currency_id');

        $currencyIds = $tiers->keys()->merge($tierStats->filter(fn ($s) => $s->migrated_tiers > 0)->keys())->unique();

        $rows = Currency::query()
            ->whereIn('id', $currencyIds)
            ->get()
            ->map(function (Currency $currency) use ($tiers, $tierStats, $balances, $balanceError, $running) {
                $symbol  = strtoupper((string) $currency->symbol);
                $pending = $tiers[$currency->id] ?? collect();
                $price   = (float) (cache("market:price:{$symbol}USDT") ?? 0);

                $required = 0.0;
                $hit      = 0;
                $hitAmount = 0.0;
                foreach ($pending as $tier) {
                    $amount = (float) $tier->amount_to_sell;
                    $target = $tier->target_type === 'price'
                        ? (float) $tier->target_value
                        : (float) $tier->avg_buy_price * (1 + (float) $tier->target_value / 100);
                    $required += $amount;
                    if ($price > 0 && $price >= $target) {
                        $hit++;
                        $hitAmount += $amount;
                    }
                }

                $balance   = $balances[$symbol] ?? null;
                $available = $balanceError === null ? (float) ($balance['available'] ?? 0) : null;
                $shortfall = $available !== null ? max(0, $required - $available) : null;

                return (object) [
                    'currency'       => $currency,
                    'symbol'         => $symbol,
                    'price'          => $price > 0 ? $price : null,
                    'pending_tiers'  => $pending->count(),
                    'users'          => $pending->pluck('user_id')->unique()->count(),
                    'orders'         => $pending->pluck('bot_order_id')->unique()->count(),
                    'required'       => $required,
                    'hit_tiers'      => $hit,
                    'hit_amount'     => $hitAmount,
                    'filled_tiers'   => (int) ($tierStats[$currency->id]->filled_tiers ?? 0),
                    'migrated_tiers' => (int) ($tierStats[$currency->id]->migrated_tiers ?? 0),
                    'available'      => $available,
                    'frozen'         => $balanceError === null ? (float) ($balance['frozen'] ?? 0) : null,
                    'shortfall'      => $shortfall,
                    'sufficient'     => $shortfall !== null && $shortfall <= 0,
                    'running'        => $running[$currency->id] ?? null,
                ];
            })
            ->sortBy([
                fn ($a, $b) => ($b->pending_tiers > 0) <=> ($a->pending_tiers > 0),
                fn ($a, $b) => $b->required * ($b->price ?? 0) <=> $a->required * ($a->price ?? 0),
            ])
            ->values();

        $migrations = BotSellMigration::with('currency')
            ->latest('id')
            ->limit(20)
            ->get();

        return view('dashboard.bot.sell-migration.index', compact('rows', 'migrations', 'balanceError'));
    }

    public function status(BotSellMigration $migration): JsonResponse
    {
        $migration->failIfOrphaned();
        $migration->load('currency');

        return response()->json(['ok' => true, 'migration' => $migration->toAdminArray()]);
    }

    /**
     * Per-tier outcome of one run (as recorded by api-service) joined with the
     * tier's current row, so the admin sees what was placed on the reference
     * exchange or why it failed.
     */
    public function report(BotSellMigration $migration): JsonResponse
    {
        $migration->failIfOrphaned();
        $migration->load('currency');

        $results   = collect($migration->details['tiers'] ?? []);
        $livePrice = (string) ($migration->details['live_price'] ?? '0');
        $sells     = BotSellOrder::with('buyExecution.botOrder.user')
            ->whereIn('id', $results->pluck('sell_order_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $rows = $results->map(function (array $r) use ($sells, $livePrice) {
            $sell      = $sells->get($r['sell_order_id'] ?? null);
            $execution = $sell?->buyExecution;
            $user      = $execution?->botOrder?->user;
            $target    = (string) ($r['target_price'] ?? '0');

            return $r + [
                'error_hint'         => isset($r['error']) ? self::errorHint((string) $r['error']) : null,
                'above_live_percent' => is_numeric($target) && is_numeric($livePrice) && bccomp($livePrice, '0', 8) > 0 && bccomp($target, '0', 8) > 0
                    ? bcmul(bcsub(bcdiv($target, $livePrice, 8), '1', 8), '100', 2)
                    : null,
                'bot_order_id'  => $execution?->bot_order_id,
                'bot_order_url' => $execution ? route('admin.bot.order.show', $execution->bot_order_id) : null,
                'user_label'    => $user?->email ?: $user?->mobile ?: ($user ? '#' . $user->id : null),
                'avg_buy_price' => $execution?->avg_buy_price !== null ? (string) $execution->avg_buy_price : null,
                'target_type'   => $sell?->target_type,
                'target_value'  => $sell?->target_value !== null ? (string) $sell->target_value : null,
                'share_percent' => $sell?->share_percent !== null ? (string) $sell->share_percent : null,
                'current'       => $sell ? [
                    'status'            => $sell->status,
                    'exchange'          => $sell->exchange,
                    'exchange_order_id' => $sell->exchange_order_id !== null ? (string) $sell->exchange_order_id : null,
                    'amount_to_sell'    => (string) $sell->amount_to_sell,
                    'cancel_reason'     => $sell->cancel_reason,
                    'migrated_at'       => $sell->migrated_at?->toIso8601String(),
                ] : null,
            ];
        })->values();

        $summary = $migration->toAdminArray();
        unset($summary['details']);

        $hasPriceRangeFailures = $results->contains(fn (array $r) => str_contains((string) ($r['error'] ?? ''), 'PERCENT_PRICE'));

        return response()->json([
            'ok'          => true,
            'migration'   => $summary + [
                'live_price' => $migration->details['live_price'] ?? null,
                'close'      => $migration->details['close'] ?? null,
            ],
            'price_limit' => $hasPriceRangeFailures && $summary['currency_symbol'] !== ''
                ? $this->sellPriceLimit($summary['currency_symbol'] . 'USDT')
                : null,
            'rows'        => $rows,
        ]);
    }

    /**
     * Binance's PERCENT_PRICE(_BY_SIDE) filter for SELL orders: a limit price is
     * accepted only between avgPrice × askMultiplierDown and avgPrice × askMultiplierUp,
     * where avgPrice is the weighted average of the last avgPriceMins minutes.
     *
     * @return array{multiplier_up:string, multiplier_down:string, max_percent_above:string, avg_price_mins:int, avg_price:?string, max_price:?string}|null
     */
    private function sellPriceLimit(string $market): ?array
    {
        try {
            return Cache::remember("bot:sell-migration:price-limit:{$market}", now()->addMinutes(5), function () use ($market) {
                $base = config('exchanges.binance.base_url');
                $info = Http::timeout(10)->get($base . '/api/v3/exchangeInfo', ['symbol' => $market]);
                if (! $info->ok()) {
                    throw new \RuntimeException('exchangeInfo: ' . $info->body());
                }

                $filter = collect($info->json('symbols.0.filters') ?? [])
                    ->first(fn ($f) => in_array($f['filterType'] ?? null, ['PERCENT_PRICE_BY_SIDE', 'PERCENT_PRICE'], true));
                $up = (string) ($filter['askMultiplierUp'] ?? $filter['multiplierUp'] ?? '');
                if (! is_numeric($up)) {
                    return null;
                }

                $avg = Http::timeout(10)->get($base . '/api/v3/avgPrice', ['symbol' => $market])->json('price');
                $avg = is_numeric($avg) ? (string) $avg : null;

                return [
                    'multiplier_up'     => $up,
                    'multiplier_down'   => (string) ($filter['askMultiplierDown'] ?? $filter['multiplierDown'] ?? ''),
                    'max_percent_above' => bcmul(bcsub($up, '1', 8), '100', 2),
                    'avg_price_mins'    => (int) ($filter['avgPriceMins'] ?? 0),
                    'avg_price'         => $avg,
                    'max_price'         => $avg !== null ? bcmul($avg, $up, 8) : null,
                ];
            });
        } catch (\Throwable $e) {
            Log::warning('bot.sell_migration.price_limit_failed', ['market' => $market, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Admin-facing explanation for the raw errors a run records.
     */
    private static function errorHint(string $error): ?string
    {
        return match (true) {
            str_contains($error, 'PERCENT_PRICE') =>
                'قیمت فروش این پله خیلی بالاتر از قیمت فعلی بازار است و Binance فعلاً چنین سفارشی را قبول نمی‌کند.',
            str_contains($error, 'NOTIONAL') =>
                'ارزش سفارش (مقدار × قیمت) کمتر از حداقل ارزش مجاز Binance برای این بازار است.',
            str_contains($error, 'LOT_SIZE') =>
                'مقدار سفارش با حداقل/گام مجاز مقدار در Binance (LOT_SIZE) سازگار نیست.',
            str_contains($error, 'PRICE_FILTER') =>
                'قیمت سفارش با حداقل/حداکثر یا گام قیمت مجاز Binance (PRICE_FILTER) سازگار نیست.',
            str_contains($error, '-2010'), stripos($error, 'insufficient balance') !== false =>
                'موجودی آزاد این ارز در حساب Binance برای ثبت سفارش کافی نبود.',
            str_contains($error, '-1021') =>
                'اختلاف ساعت سرور با Binance بیش از حد مجاز بود؛ درخواست رد شد.',
            str_contains($error, 'no longer OPEN') =>
                'وضعیت پله هنگام اجرا تغییر کرده بود (دیگر باز و در انتظار انتقال نبود)؛ کاری روی آن انجام نشد.',
            str_contains($error, 'merge of sibling tiers failed') =>
                'ادغام پله‌های کوچک همین خرید انجام نشد، بنابراین این پله هم ثبت نشد.',
            default => null,
        };
    }

    /**
     * @return array{0: array<string, array{available:string, frozen:string}>, 1: ?string}
     */
    private function binanceBalances(): array
    {
        try {
            $balances = AssetFactory::make('binance')->getBalance();
        } catch (\Throwable $e) {
            Log::error('bot.sell_migration.balance_failed', ['error' => $e->getMessage()]);

            return [[], 'امکان ارتباط با Binance نیست: ' . $e->getMessage()];
        }
        $map = [];
        foreach ($balances as $dto) {
            $map[strtoupper((string) $dto->getCcy())] = [
                'available' => (string) $dto->getAvailable(),
                'frozen'    => (string) $dto->getFrozen(),
            ];
        }

        return [$map, null];
    }
}
