<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotSellOrder;
use App\Models\Currency;
use App\Services\Bot\WaitingSellTiersService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Backend for the admin-panel "waiting sell tiers" page: per-coin details
 * (ceiling, live price, how far each tier is from being placeable) and the
 * manual "place now" actions. Protected by `bot-admin-auth`.
 */
class BotWaitingSellsController extends Controller
{
    public function __construct(private readonly WaitingSellTiersService $service) {}

    public function lastRun(): JsonResponse
    {
        return response()->json(['ok' => true, 'last_run' => $this->service->lastRun()]);
    }

    public function details(int $currencyId): JsonResponse
    {
        $currency = Currency::find($currencyId);
        if (! $currency) {
            return response()->json(['ok' => false, 'error' => 'ارز یافت نشد.'], 404);
        }

        return response()->json(['ok' => true] + $this->service->details($currency));
    }

    public function placeCurrency(Request $request, int $currencyId): JsonResponse
    {
        $currency = Currency::find($currencyId);
        if (! $currency) {
            return response()->json(['ok' => false, 'error' => 'ارز یافت نشد.'], 404);
        }

        $settings = BotGlobalSettings::current();
        $summary  = $this->service->placeCurrency(
            $currency,
            max(1, (int) $settings->waiting_max_per_run),
            max(0, (int) $settings->waiting_rotation_max_per_run),
        );
        if ($summary['skipped'] !== null) {
            return response()->json(['ok' => false, 'error' => $summary['skipped']], 409);
        }

        Log::channel('smart-bot')->info('bot.waiting.admin_place_currency', [
            'currency' => $summary['symbol'],
            'admin_id' => $request->header('X-Admin-Id'),
        ] + array_intersect_key($summary, array_flip(['waiting', 'placed', 'completed', 'rejected', 'rotated', 'still_waiting'])));

        return response()->json([
            'ok'      => true,
            'message' => sprintf(
                '%s: %d پله ثبت شد، %d پله با Market فروخته شد، %d جابه‌جایی انجام شد، %d پله رد شد و %d پله همچنان در انتظار است (%d به‌خاطر سقف قیمت، %d به‌خاطر سقف تعداد سفارش).',
                $summary['symbol'], $summary['placed'], $summary['completed'], $summary['rotated'], $summary['rejected'],
                $summary['still_waiting'], $summary['price_waiting'], $summary['slot_waiting'],
            ),
            'summary' => $summary,
        ]);
    }

    public function placeTier(Request $request, int $sellOrderId): JsonResponse
    {
        $tier = BotSellOrder::with('botBuyExecution.currency')->find($sellOrderId);
        if (! $tier) {
            return response()->json(['ok' => false, 'error' => 'پله یافت نشد.'], 404);
        }

        $result = $this->service->placeTier($tier);

        Log::channel('smart-bot')->info('bot.waiting.admin_place_tier', [
            'sell_order_id' => $tier->id,
            'admin_id'      => $request->header('X-Admin-Id'),
            'outcome'       => $result['outcome'],
            'error'         => $result['error'] ?? null,
        ]);

        return match ($result['outcome']) {
            'placed'    => response()->json(['ok' => true, 'message' => "پله #{$tier->id} در Binance ثبت شد (سفارش {$result['exchange_order_id']}).", 'result' => $result]),
            'completed' => response()->json(['ok' => true, 'message' => "قیمت از تارگت پله #{$tier->id} گذشته بود؛ با Market فروخته شد و تسویه در صف است.", 'result' => $result]),
            'skipped'   => response()->json(['ok' => false, 'error' => $result['error'], 'result' => $result], 409),
            default     => response()->json(['ok' => false, 'error' => 'Binance سفارش را نپذیرفت و پله در انتظار ماند: '.($result['error'] ?? 'نامشخص'), 'result' => $result], 422),
        };
    }
}
