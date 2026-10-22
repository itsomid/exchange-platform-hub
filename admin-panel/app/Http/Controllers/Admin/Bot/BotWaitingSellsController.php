<?php

namespace App\Http\Controllers\Admin\Bot;

use App\Http\Controllers\Controller;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotSellOrder;
use App\Models\Currency;
use App\Services\Bot\BotAdminApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * "Waiting sell tiers" page: OPEN tiers kept off Binance because their price
 * is above its PERCENT_PRICE_BY_SIDE ceiling, grouped per coin. Details and
 * the "place now" actions are proxied to api-service
 * (BotOrderController::waitingSells*); api-service's bot:place-waiting-sells
 * places them automatically while waiting_sells_auto_place is on.
 */
class BotWaitingSellsController extends Controller
{
    public function index(BotAdminApiClient $botApi): View
    {
        $tiers = BotSellOrder::query()
            ->join('bot_buy_executions', 'bot_buy_executions.id', '=', 'bot_sell_orders.bot_buy_execution_id')
            ->join('bot_orders', 'bot_orders.id', '=', 'bot_buy_executions.bot_order_id')
            ->where('bot_sell_orders.status', 'OPEN')
            ->where('bot_sell_orders.placement_status', BotSellOrder::PLACEMENT_WAITING)
            ->get([
                'bot_buy_executions.currency_id',
                'bot_orders.user_id',
                'bot_buy_executions.bot_order_id',
                'bot_buy_executions.avg_buy_price',
                'bot_sell_orders.target_type',
                'bot_sell_orders.target_value',
                'bot_sell_orders.amount_to_sell',
                'bot_sell_orders.waiting_since',
                'bot_sell_orders.last_placement_check_at',
                'bot_sell_orders.last_placement_error',
                'bot_sell_orders.placement_attempts',
            ])
            ->groupBy('currency_id');

        $rows = Currency::query()
            ->whereIn('id', $tiers->keys())
            ->get()
            ->map(function (Currency $currency) use ($tiers) {
                $symbol  = strtoupper((string) $currency->symbol);
                $waiting = $tiers[$currency->id];
                $price   = (float) (cache("market:price:{$symbol}USDT") ?? 0);
                $targets = $waiting->map(fn ($t) => $t->target_type === 'price'
                    ? (float) $t->target_value
                    : (float) $t->avg_buy_price * (1 + (float) $t->target_value / 100));
                $lowest  = $targets->min();

                return (object) [
                    'currency'      => $currency,
                    'symbol'        => $symbol,
                    'price'         => $price > 0 ? $price : null,
                    'tiers'         => $waiting->count(),
                    'amount'        => $waiting->sum(fn ($t) => (float) $t->amount_to_sell),
                    'users'         => $waiting->pluck('user_id')->unique()->count(),
                    'orders'        => $waiting->pluck('bot_order_id')->unique()->count(),
                    'lowest_target' => $lowest,
                    'lowest_above'  => $price > 0 && $lowest ? ($lowest / $price - 1) * 100 : null,
                    'oldest'        => $waiting->min('waiting_since'),
                    'last_check'    => $waiting->max('last_placement_check_at'),
                    'attempts'      => (int) $waiting->sum('placement_attempts'),
                    'last_error'    => $waiting->sortByDesc('last_placement_check_at')->pluck('last_placement_error')->filter()->first(),
                ];
            })
            ->sortByDesc(fn ($r) => $r->amount * ($r->price ?? 0))
            ->values();

        $autoPlace = (bool) BotGlobalSettings::current()->waiting_sells_auto_place;
        $lastRun   = $this->lastRun($botApi);

        return view('dashboard.bot.waiting-sells.index', compact('rows', 'autoPlace', 'lastRun'));
    }

    public function toggleAutoPlace(Request $request): JsonResponse
    {
        $data = $request->validate(['enabled' => ['required', 'boolean']]);

        $settings = BotGlobalSettings::current();
        $settings->waiting_sells_auto_place = (bool) $data['enabled'];
        $settings->save();

        Log::info('bot.waiting_sells.auto_place_toggled', [
            'admin_id' => auth('admin')->id(),
            'enabled'  => $settings->waiting_sells_auto_place,
        ]);

        return response()->json([
            'ok'      => true,
            'enabled' => $settings->waiting_sells_auto_place,
            'message' => $settings->waiting_sells_auto_place
                ? 'ثبت خودکار پله‌های در انتظار فعال شد.'
                : 'ثبت خودکار پله‌های در انتظار غیرفعال شد.',
        ]);
    }

    private function lastRun(BotAdminApiClient $botApi): ?array
    {
        try {
            $response = $botApi->waitingSellsLastRun();

            return $response->successful() ? $response->json('last_run') : null;
        } catch (\Throwable $e) {
            Log::warning('bot.waiting_sells.last_run_failed', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
