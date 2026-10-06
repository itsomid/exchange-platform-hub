<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Services\Bot\BotCurrencyCancelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Backend for the admin-panel "cancel by coin" page: preview what canceling
 * one coin across every bot order would refund, and queue the actual run.
 * Protected by `bot-admin-auth` (shared X-Internal-Token header).
 */
class BotCurrencyCancelController extends Controller
{
    public function __construct(private readonly BotCurrencyCancelService $service) {}

    public function preview(int $currencyId): JsonResponse
    {
        $currency = Currency::find($currencyId);
        if (! $currency) {
            return response()->json(['ok' => false, 'error' => 'ارز یافت نشد.'], 404);
        }

        try {
            return response()->json(['ok' => true] + $this->service->preview($currency));
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->error('bot.currency_cancel.preview_exception', [
                'currency_id' => $currencyId,
                'exception'   => $e::class,
                'message'     => $e->getMessage(),
                'file'        => $e->getFile().':'.$e->getLine(),
            ]);

            return response()->json([
                'ok'    => false,
                'error' => 'خطای داخلی در پیش‌نمایش لغو: '.$e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request, int $currencyId): JsonResponse
    {
        $data = $request->validate([
            'cancel_on_exchange' => ['required', 'boolean'],
            'sell_on_exchange'   => ['required', 'boolean'],
            'reason'             => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $currency = Currency::find($currencyId);
        if (! $currency) {
            return response()->json(['ok' => false, 'error' => 'ارز یافت نشد.'], 404);
        }

        $adminId = $request->header('X-Admin-Id');

        try {
            $cancellation = $this->service->start(
                currency:         $currency,
                cancelOnExchange: (bool) $data['cancel_on_exchange'],
                sellOnExchange:   (bool) $data['sell_on_exchange'],
                reason:           trim($data['reason']),
                adminId:          $adminId !== null && $adminId !== '' ? (int) $adminId : null,
                adminLabel:       $request->header('X-Admin-Label') ?: null,
            );
        } catch (\DomainException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 409);
        }

        return response()->json([
            'ok'              => true,
            'message'         => "لغو ارز {$currency->symbol} در صف اجرا قرار گرفت.",
            'cancellation_id' => $cancellation->id,
            'status'          => $cancellation->status,
        ]);
    }
}
