<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Services\Bot\BotSellMigrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Backend for the admin-panel "move sell tiers to Binance" page: preview what
 * re-placing one coin's CoinEx tiers would do, and queue the actual run.
 * Protected by `bot-admin-auth` (shared X-Internal-Token header).
 */
class BotSellMigrationController extends Controller
{
    public function __construct(private readonly BotSellMigrationService $service) {}

    public function preview(int $currencyId): JsonResponse
    {
        $currency = Currency::find($currencyId);
        if (! $currency) {
            return response()->json(['ok' => false, 'error' => 'ارز یافت نشد.'], 404);
        }

        try {
            return response()->json(['ok' => true] + $this->service->preview($currency));
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->error('bot.sell_migration.preview_exception', [
                'currency_id' => $currencyId,
                'exception'   => $e::class,
                'message'     => $e->getMessage(),
                'file'        => $e->getFile().':'.$e->getLine(),
            ]);

            return response()->json([
                'ok'    => false,
                'error' => 'خطای داخلی در پیش‌نمایش انتقال: '.$e->getMessage(),
            ], 500);
        }
    }

    public function start(Request $request, int $currencyId): JsonResponse
    {
        $currency = Currency::find($currencyId);
        if (! $currency) {
            return response()->json(['ok' => false, 'error' => 'ارز یافت نشد.'], 404);
        }

        $adminId = $request->header('X-Admin-Id');

        try {
            $migration = $this->service->start(
                currency:   $currency,
                adminId:    $adminId !== null && $adminId !== '' ? (int) $adminId : null,
                adminLabel: $request->header('X-Admin-Label') ?: null,
            );
        } catch (\DomainException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 409);
        }

        return response()->json([
            'ok'           => true,
            'message'      => "انتقال پله‌های فروش {$currency->symbol} به Binance در صف اجرا قرار گرفت.",
            'migration_id' => $migration->id,
            'status'       => $migration->status,
        ]);
    }
}
