<?php

namespace App\Http\Controllers\Admin\Bot;

use App\Http\Controllers\Controller;
use App\Models\Bot\BotSellMigration;
use App\Models\Bot\BotSellOrder;
use App\Models\Currency;
use App\Services\Exchanges\Asset\AssetFactory;
use Illuminate\Http\JsonResponse;
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
