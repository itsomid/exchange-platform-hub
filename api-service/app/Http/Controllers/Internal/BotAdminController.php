<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotUserSettings;
use App\Models\Bot\BotWallet;
use App\Models\User;
use App\Services\Bot\BotOrderCancelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Backend for the admin-panel "cancel bot order" / "cancel all user orders"
 * buttons. Reuses BotOrderCancelService (same flow as the user-facing cancel):
 * for every OPEN sell tier the limit sell is first canceled on the reference
 * exchange, then exactly that tier's amount_to_sell is market-sold, so the
 * total liquidated equals the sum of the canceled tiers only (filled tiers
 * are never touched).
 *
 * Cancels are refused (409) while the order still has work in flight:
 *   - buy executions in PENDING/BUYING (a buy could fill AFTER the cancel and
 *     leave coins stranded on the omnibus account), or
 *   - BOUGHT executions whose sell tiers haven't been fanned out yet
 *     (OpenSellOrdersJob pending — cancel would miss that held coin entirely).
 *
 * Protected by `bot-admin-auth` middleware (shared X-Internal-Token header),
 * available in production.
 */
class BotAdminController extends Controller
{
    private const SCALE = 8;

    public function __construct(private readonly BotOrderCancelService $cancelService) {}

    public function cancelPreview(int $orderId): JsonResponse
    {
        Log::channel('smart-bot')->info('bot.admin.cancel_preview.start', [
            'bot_order_id' => $orderId,
        ]);

        try {
            $order = BotOrder::find($orderId);
            if (! $order) {
                Log::channel('smart-bot')->warning('bot.admin.cancel_preview.not_found', [
                    'bot_order_id' => $orderId,
                ]);

                return response()->json(['ok' => false, 'error' => 'سفارش یافت نشد.'], 404);
            }

            if ($order->status === 'CANCELED') {
                Log::channel('smart-bot')->warning('bot.admin.cancel_preview.already_canceled', [
                    'bot_order_id' => $orderId,
                    'status'       => $order->status,
                ]);

                return response()->json(['ok' => false, 'error' => 'این سفارش قبلاً لغو شده است.'], 422);
            }

            $preview = $this->cancelService->preview($order);

            Log::channel('smart-bot')->info('bot.admin.cancel_preview.ok', [
                'bot_order_id'     => $orderId,
                'user_id'          => $order->user_id,
                'open_sell_orders' => $preview['open_sell_orders'] ?? null,
                'sell_on_exchange' => $preview['sell_on_exchange'] ?? null,
                'in_flight'        => $this->inFlightSummary($order),
            ]);

            return response()->json([
                'ok'               => true,
                'mode'             => 'single',
                'sell_on_exchange' => $preview['sell_on_exchange'],
                'in_flight'        => $this->inFlightSummary($order),
                'orders'           => [$preview],
                'totals'           => [
                    'open_sell_orders'  => $preview['open_sell_orders'],
                    'total_current_value' => $preview['total_current_value'],
                    'total_fee'         => $preview['total_fee'],
                    'refund_to_balance' => $preview['refund_to_balance'],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->error('bot.admin.cancel_preview.exception', [
                'bot_order_id' => $orderId,
                'exception'    => $e::class,
                'message'      => $e->getMessage(),
                'file'         => $e->getFile().':'.$e->getLine(),
                'trace'        => collect($e->getTrace())->take(8)->map(
                    fn ($f) => ($f['file'] ?? '?').':'.($f['line'] ?? '?').' '.($f['function'] ?? '')
                )->all(),
            ]);

            return response()->json([
                'ok'    => false,
                'error' => 'خطای داخلی در پیش‌نمایش لغو: '.$e->getMessage(),
            ], 500);
        }
    }

    public function cancel(int $orderId): JsonResponse
    {
        $order = BotOrder::find($orderId);
        if (! $order) {
            return response()->json(['ok' => false, 'error' => 'سفارش یافت نشد.'], 404);
        }

        if ($order->status === 'CANCELED') {
            return response()->json(['ok' => false, 'error' => 'این سفارش قبلاً لغو شده است.'], 422);
        }

        if ($blocked = $this->denyIfInFlight([$order])) {
            return $blocked;
        }

        $result = $this->cancelService->cancel($order);

        Log::channel('smart-bot')->info('bot.admin.order_canceled', [
            'bot_order_id' => $order->id,
            'user_id'      => $order->user_id,
            'result'       => $result,
        ]);

        return response()->json([
            'ok'      => true,
            'message' => "سفارش #{$order->id} لغو شد ({$result['canceled']} پله فروش).",
            'results' => [$result],
            'totals'  => [
                'orders_canceled' => 1,
                'sell_orders'     => $result['canceled'],
                'total_fee'       => $result['total_fee'],
                'total_refund'    => $result['total_refund'],
            ],
            'wallet'  => $this->walletSnapshot($order->user_id),
        ]);
    }

    public function cancelAllPreview(int $userId): JsonResponse
    {
        Log::channel('smart-bot')->info('bot.admin.cancel_all_preview.start', [
            'user_id' => $userId,
        ]);

        try {
            if (! User::whereKey($userId)->exists()) {
                Log::channel('smart-bot')->warning('bot.admin.cancel_all_preview.user_not_found', [
                    'user_id' => $userId,
                ]);

                return response()->json(['ok' => false, 'error' => 'کاربر یافت نشد.'], 404);
            }

            $orders = $this->cancelableOrders($userId);

            $previews    = [];
            $totalSells  = 0;
            $totalValue  = '0';
            $totalFee    = '0';
            $totalRefund = '0';
            $sellOnExch  = true;
            $inFlight    = [];

            foreach ($orders as $order) {
                $preview    = $this->cancelService->preview($order);
                $previews[] = $preview;
                $sellOnExch = (bool) $preview['sell_on_exchange'];

                $totalSells += (int) $preview['open_sell_orders'];
                $totalValue  = bcadd($totalValue, $preview['total_current_value'], self::SCALE);
                $totalFee    = bcadd($totalFee, $preview['total_fee'], self::SCALE);
                $totalRefund = bcadd($totalRefund, $preview['refund_to_balance'], self::SCALE);

                if ($flight = $this->inFlightSummary($order)) {
                    $inFlight[] = $flight;
                }
            }

            Log::channel('smart-bot')->info('bot.admin.cancel_all_preview.ok', [
                'user_id'          => $userId,
                'orders'           => count($previews),
                'open_sell_orders' => $totalSells,
                'in_flight_count'  => count($inFlight),
            ]);

            return response()->json([
                'ok'               => true,
                'mode'             => 'all',
                'sell_on_exchange' => $sellOnExch,
                'in_flight'        => $inFlight,
                'orders'           => $previews,
                'totals'           => [
                    'orders'            => count($previews),
                    'open_sell_orders'  => $totalSells,
                    'total_current_value' => $totalValue,
                    'total_fee'         => $totalFee,
                    'refund_to_balance' => $totalRefund,
                ],
                'wallet' => $this->walletSnapshot($userId),
            ]);
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->error('bot.admin.cancel_all_preview.exception', [
                'user_id'   => $userId,
                'exception' => $e::class,
                'message'   => $e->getMessage(),
                'file'      => $e->getFile().':'.$e->getLine(),
                'trace'     => collect($e->getTrace())->take(8)->map(
                    fn ($f) => ($f['file'] ?? '?').':'.($f['line'] ?? '?').' '.($f['function'] ?? '')
                )->all(),
            ]);

            return response()->json([
                'ok'    => false,
                'error' => 'خطای داخلی در پیش‌نمایش لغو همه: '.$e->getMessage(),
            ], 500);
        }
    }

    public function cancelAll(int $userId): JsonResponse
    {
        if (! User::whereKey($userId)->exists()) {
            return response()->json(['ok' => false, 'error' => 'کاربر یافت نشد.'], 404);
        }

        $orders = $this->cancelableOrders($userId);

        if ($blocked = $this->denyIfInFlight($orders->all())) {
            return $blocked;
        }

        // Turn auto-trade off FIRST so nothing (reinvest / new signal cycle)
        // re-opens positions while we are freeing the user's funds.
        $autoTradeDisabled = BotUserSettings::where('user_id', $userId)
            ->where('auto_trade_enabled', true)
            ->update(['auto_trade_enabled' => false]) > 0;

        $results     = [];
        $totalSells  = 0;
        $totalFee    = '0';
        $totalRefund = '0';

        foreach ($orders as $order) {
            $result    = $this->cancelService->cancel($order);
            $results[] = $result;

            $totalSells += (int) $result['canceled'];
            $totalFee    = bcadd($totalFee, $result['total_fee'], self::SCALE);
            $totalRefund = bcadd($totalRefund, $result['total_refund'], self::SCALE);
        }

        $sweptResidual = $this->sweepLockedResidual($userId);

        Log::channel('smart-bot')->info('bot.admin.cancel_all', [
            'user_id'             => $userId,
            'orders_canceled'     => count($results),
            'sell_orders'         => $totalSells,
            'total_refund'        => $totalRefund,
            'auto_trade_disabled' => $autoTradeDisabled,
            'swept_locked_residual' => $sweptResidual,
        ]);

        return response()->json([
            'ok'      => true,
            'message' => count($results) > 0
                ? sprintf('%d سفارش (%d پله فروش) لغو شد و وجوه کاربر آزاد گردید.', count($results), $totalSells)
                : 'سفارش باز و قابل لغوی وجود نداشت.' . ($autoTradeDisabled ? ' ربات کاربر خاموش شد.' : ''),
            'auto_trade_disabled' => $autoTradeDisabled,
            'results' => $results,
            'totals'  => [
                'orders_canceled' => count($results),
                'sell_orders'     => $totalSells,
                'total_fee'       => $totalFee,
                'total_refund'    => $totalRefund,
                'swept_locked_residual' => $sweptResidual,
            ],
            'wallet' => $this->walletSnapshot($userId),
        ]);
    }

    /* ──────────────────────── internals ──────────────────────── */

    /**
     * After a full cancel-all, nothing of the user's capital should remain
     * locked. Historic settlements released only cost_basis (smaller than
     * allocated_usdt when buy fees were paid in the base currency), and the
     * proportional release still truncates at 8 decimals, so a small residual
     * can survive. Once the user has no open sell tiers and no buy executions
     * in flight, that residual is provably not backing any position — zero it.
     *
     * @return string the amount released ('0' when nothing was swept)
     */
    private function sweepLockedResidual(int $userId): string
    {
        return DB::transaction(function () use ($userId) {
            $wallet = BotWallet::where('user_id', $userId)->lockForUpdate()->first();
            if (! $wallet || bccomp((string) $wallet->locked_balance, '0', self::SCALE) <= 0) {
                return '0';
            }

            $hasOpenSells = BotSellOrder::query()
                ->where('status', BotSellOrder::STATUS_OPEN)
                ->whereHas('botBuyExecution.botOrder', fn ($q) => $q->where('user_id', $userId))
                ->exists();

            $hasInFlightBuys = BotBuyExecution::query()
                ->whereIn('status', [BotBuyExecution::STATUS_PENDING, BotBuyExecution::STATUS_BUYING])
                ->whereHas('botOrder', fn ($q) => $q->where('user_id', $userId))
                ->exists();

            if ($hasOpenSells || $hasInFlightBuys) {
                return '0';
            }

            $residual = (string) $wallet->locked_balance;
            $wallet->update(['locked_balance' => '0']);

            Log::channel('smart-bot')->info('bot.admin.locked_residual_swept', [
                'user_id'  => $userId,
                'residual' => $residual,
            ]);

            return $residual;
        });
    }

    /**
     * Orders still worth canceling: any non-terminal order plus any order
     * (whatever its status) that still has OPEN sell tiers.
     *
     * @return \Illuminate\Support\Collection<int,BotOrder>
     */
    private function cancelableOrders(int $userId)
    {
        return BotOrder::query()
            ->where('user_id', $userId)
            ->where('status', '!=', 'CANCELED')
            ->where(function ($q) {
                $q->whereNotIn('status', ['FAILED', 'FILLED'])
                    ->orWhereHas('buyExecutions.sellOrders', function ($s) {
                        $s->where('status', BotSellOrder::STATUS_OPEN);
                    });
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array{order_id:int, buys_in_flight:int, sells_pending:int}|null
     */
    private function inFlightSummary(BotOrder $order): ?array
    {
        $buysInFlight = BotBuyExecution::query()
            ->where('bot_order_id', $order->id)
            ->whereIn('status', [BotBuyExecution::STATUS_PENDING, BotBuyExecution::STATUS_BUYING])
            ->count();

        // BOUGHT but no sell tiers yet → OpenSellOrdersJob still pending; the
        // held coin would be invisible to the cancel flow.
        $sellsPending = BotBuyExecution::query()
            ->where('bot_order_id', $order->id)
            ->where('status', BotBuyExecution::STATUS_BOUGHT)
            ->whereDoesntHave('sellOrders')
            ->count();

        if ($buysInFlight === 0 && $sellsPending === 0) {
            return null;
        }

        return [
            'order_id'       => (int) $order->id,
            'buys_in_flight' => $buysInFlight,
            'sells_pending'  => $sellsPending,
        ];
    }

    /**
     * @param array<int,BotOrder> $orders
     */
    private function denyIfInFlight(array $orders): ?JsonResponse
    {
        $inFlight = [];
        foreach ($orders as $order) {
            if ($flight = $this->inFlightSummary($order)) {
                $inFlight[] = $flight;
            }
        }

        if (empty($inFlight)) {
            return null;
        }

        $ids = implode('، ', array_map(fn ($f) => '#' . $f['order_id'], $inFlight));

        return response()->json([
            'ok'        => false,
            'error'     => "سفارش {$ids} هنوز در حال پردازش خرید/ثبت پله‌های فروش در صرافی مرجع است. چند لحظه صبر کنید و دوباره تلاش کنید.",
            'in_flight' => $inFlight,
        ], 409);
    }

    private function walletSnapshot(int $userId): ?array
    {
        $wallet = BotWallet::where('user_id', $userId)->first();
        if (! $wallet) {
            return null;
        }

        return [
            'balance'        => (string) $wallet->balance,
            'locked_balance' => (string) $wallet->locked_balance,
            'profit_balance' => (string) $wallet->profit_balance,
        ];
    }
}
