<?php

namespace App\Http\Controllers\Admin\Bot;

use App\Http\Controllers\Controller;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotCurrencyCancellation;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotSignal;
use App\Models\Bot\BotTradeSettlement;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * "Cancel by coin" page: every coin ever bought in bot orders with its
 * aggregate position, plus the history of admin coin-cancel runs. The cancel
 * itself is proxied to api-service (BotOrderController::currencyCancel*).
 */
class BotCurrencyController extends Controller
{
    public function index(): View
    {
        $buys = BotBuyExecution::query()
            ->join('bot_orders', 'bot_orders.id', '=', 'bot_buy_executions.bot_order_id')
            ->whereIn('bot_buy_executions.status', ['BOUGHT', 'CLOSED'])
            ->groupBy('bot_buy_executions.currency_id')
            ->selectRaw('bot_buy_executions.currency_id')
            ->selectRaw('COUNT(DISTINCT bot_orders.user_id) as users_count')
            ->selectRaw('COUNT(DISTINCT bot_buy_executions.bot_order_id) as orders_count')
            ->selectRaw('SUM(bot_buy_executions.allocated_usdt) as total_allocated')
            ->selectRaw('SUM(bot_buy_executions.filled_amount) as total_bought')
            ->selectRaw('SUM(bot_buy_executions.filled_amount * bot_buy_executions.avg_buy_price) as total_cost')
            ->selectRaw('SUM(bot_buy_executions.buy_ref_exchange_fee) as buy_fee')
            ->get()
            ->keyBy('currency_id');

        $sells = BotSellOrder::query()
            ->join('bot_buy_executions', 'bot_buy_executions.id', '=', 'bot_sell_orders.bot_buy_execution_id')
            ->join('bot_orders', 'bot_orders.id', '=', 'bot_buy_executions.bot_order_id')
            ->groupBy('bot_buy_executions.currency_id')
            ->selectRaw('bot_buy_executions.currency_id')
            ->selectRaw("SUM(CASE WHEN bot_sell_orders.status = 'OPEN' THEN 1 ELSE 0 END) as open_tiers")
            ->selectRaw("SUM(CASE WHEN bot_sell_orders.status = 'FILLED' THEN 1 ELSE 0 END) as filled_tiers")
            ->selectRaw("SUM(CASE WHEN bot_sell_orders.status = 'CANCELED' THEN 1 ELSE 0 END) as canceled_tiers")
            ->selectRaw("SUM(CASE WHEN bot_sell_orders.status = 'OPEN' THEN bot_sell_orders.amount_to_sell ELSE 0 END) as open_amount")
            ->selectRaw("SUM(CASE WHEN bot_sell_orders.status = 'OPEN' THEN bot_sell_orders.amount_to_sell * bot_buy_executions.avg_buy_price ELSE 0 END) as open_cost")
            ->selectRaw("COUNT(DISTINCT CASE WHEN bot_sell_orders.status = 'OPEN' THEN bot_orders.user_id END) as open_users")
            ->selectRaw("COUNT(DISTINCT CASE WHEN bot_sell_orders.status = 'OPEN' THEN bot_orders.id END) as open_orders")
            ->get()
            ->keyBy('currency_id');

        $realized = BotTradeSettlement::query()
            ->join('bot_buy_executions', 'bot_buy_executions.id', '=', 'bot_trade_settlements.bot_buy_execution_id')
            ->groupBy('bot_buy_executions.currency_id')
            ->selectRaw('bot_buy_executions.currency_id')
            ->selectRaw('SUM(bot_trade_settlements.net_pnl) as realized_pnl')
            ->selectRaw('SUM(bot_trade_settlements.performance_fee) as performance_fee')
            ->get()
            ->keyBy('currency_id');

        $inFlight = BotBuyExecution::query()
            ->whereIn('status', ['PENDING', 'BUYING'])
            ->groupBy('currency_id')
            ->selectRaw('currency_id, COUNT(*) as c')
            ->pluck('c', 'currency_id');

        $signals = BotSignal::query()->pluck('is_active', 'currency_id');

        $running = BotCurrencyCancellation::query()
            ->whereIn('status', [BotCurrencyCancellation::STATUS_PENDING, BotCurrencyCancellation::STATUS_RUNNING])
            ->get()
            ->reject(fn (BotCurrencyCancellation $c) => $c->failIfOrphaned())
            ->keyBy('currency_id');

        $rows = Currency::query()
            ->whereIn('id', $buys->keys())
            ->get()
            ->map(function (Currency $currency) use ($buys, $sells, $realized, $inFlight, $signals, $running) {
                $b = $buys[$currency->id];
                $s = $sells[$currency->id] ?? null;
                $r = $realized[$currency->id] ?? null;

                $symbol      = strtoupper((string) $currency->symbol);
                $price       = (float) (cache("market:price:{$symbol}USDT") ?? 0);
                $totalBought = (float) $b->total_bought;
                $openAmount  = (float) ($s->open_amount ?? 0);
                $openCost    = (float) ($s->open_cost ?? 0);
                $openValue   = $price > 0 ? $openAmount * $price : null;

                return (object) [
                    'currency'        => $currency,
                    'symbol'          => $symbol,
                    'price'           => $price > 0 ? $price : null,
                    'signal_active'   => $signals->has($currency->id) ? (bool) $signals[$currency->id] : null,
                    'users_count'     => (int) $b->users_count,
                    'orders_count'    => (int) $b->orders_count,
                    'total_allocated' => (float) $b->total_allocated,
                    'total_bought'    => $totalBought,
                    'avg_buy_price'   => $totalBought > 0 ? (float) $b->total_cost / $totalBought : null,
                    'buy_fee'         => (float) $b->buy_fee,
                    'open_tiers'      => (int) ($s->open_tiers ?? 0),
                    'filled_tiers'    => (int) ($s->filled_tiers ?? 0),
                    'canceled_tiers'  => (int) ($s->canceled_tiers ?? 0),
                    'open_users'      => (int) ($s->open_users ?? 0),
                    'open_orders'     => (int) ($s->open_orders ?? 0),
                    'open_amount'     => $openAmount,
                    'open_cost'       => $openCost,
                    'open_value'      => $openValue,
                    'unrealized_pnl'  => $openValue !== null ? $openValue - $openCost : null,
                    'realized_pnl'    => (float) ($r->realized_pnl ?? 0),
                    'performance_fee' => (float) ($r->performance_fee ?? 0),
                    'in_flight'       => (int) ($inFlight[$currency->id] ?? 0),
                    'running'         => $running[$currency->id] ?? null,
                ];
            })
            ->sortBy([
                fn ($a, $b) => ($b->open_tiers > 0) <=> ($a->open_tiers > 0),
                fn ($a, $b) => $b->open_cost <=> $a->open_cost,
                fn ($a, $b) => $b->total_allocated <=> $a->total_allocated,
            ])
            ->values();

        $cancellations = BotCurrencyCancellation::with('currency')
            ->latest('id')
            ->limit(20)
            ->get();

        return view('dashboard.bot.currencies.index', compact('rows', 'cancellations'));
    }

    public function cancellationStatus(BotCurrencyCancellation $cancellation): JsonResponse
    {
        $cancellation->failIfOrphaned();
        $cancellation->load('currency');

        return response()->json(['ok' => true, 'cancellation' => $cancellation->toAdminArray()]);
    }
}
