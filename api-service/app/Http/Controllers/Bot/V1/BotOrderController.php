<?php

namespace App\Http\Controllers\Bot\V1;

use App\Http\Controllers\Controller;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotUserSettings;
use App\Services\Bot\BotOrderCancelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BotOrderController extends Controller
{
    public function __construct(private readonly BotOrderCancelService $cancelService) {}

    /**
     * Aggregated capital allocation across the user's currently-active bot
     * positions (BOUGHT executions that still have at least one OPEN sell
     * order). Used by the dashboard "ترکیب تخصیص ارزها" pie chart.
     *
     * Response shape:
     *   data: [
     *     { currency: 'BTC', name: 'Bitcoin', allocated_usdt: '120.00', amount: '0.0023', logo: '...' },
     *     ...
     *   ]
     *   meta: { total_usdt: '200.00', currencies_count: 3 }
     */
    public function allocations(): JsonResponse
    {
        $userId = Auth::id();

        $rows = BotBuyExecution::query()
            ->select('bot_buy_executions.currency_id')
            ->selectRaw('SUM(bot_buy_executions.allocated_usdt) as allocated_usdt')
            ->selectRaw('SUM(bot_buy_executions.filled_amount) as amount')
            ->join('bot_orders', 'bot_orders.id', '=', 'bot_buy_executions.bot_order_id')
            ->where('bot_orders.user_id', $userId)
            ->where('bot_buy_executions.status', BotBuyExecution::STATUS_BOUGHT)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('bot_sell_orders')
                    ->whereColumn('bot_sell_orders.bot_buy_execution_id', 'bot_buy_executions.id')
                    ->where('bot_sell_orders.status', BotSellOrder::STATUS_OPEN);
            })
            ->groupBy('bot_buy_executions.currency_id')
            ->with('currency:id,symbol,name,persian_name,logo')
            ->get();

        $total = '0';
        $data  = $rows->map(function (BotBuyExecution $row) use (&$total) {
            $alloc = (string) $row->allocated_usdt;
            $total = bcadd($total, $alloc, 8);
            return [
                'currency_id'    => $row->currency_id,
                'currency'       => $row->currency?->symbol,
                'name'           => $row->currency?->name,
                'persian_name'   => $row->currency?->persian_name,
                'logo'           => $row->currency?->logo,
                'allocated_usdt' => $alloc,
                'amount'         => (string) $row->amount,
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'total_usdt'       => $total,
                'currencies_count' => $data->count(),
            ],
        ]);
    }

    /**
     * Lightweight activation/progress status for the user's most recent bot
     * order. Polled by the dashboard "progressive activation" overlay after the
     * user turns the bot on, so the UI can show buy/sell job progress and reveal
     * the populated dashboard only once every execution reaches a terminal state.
     *
     * Response:
     *   data: {
     *     auto_trade_enabled, has_order, order_id, order_status, triggered_by,
     *     created_at, total, in_flight, retrying, bought, skipped, failed,
     *     sells_open, sells_pending, completed
     *   }
     */
    public function activationStatus(): JsonResponse
    {
        $userId = Auth::id();

        $settings = BotUserSettings::where('user_id', $userId)->first();
        $autoTradeEnabled = (bool) ($settings?->auto_trade_enabled);

        $order = BotOrder::where('user_id', $userId)->orderByDesc('id')->first();

        if (! $order) {
            return response()->json([
                'data' => [
                    'auto_trade_enabled' => $autoTradeEnabled,
                    'has_order'          => false,
                    'order_id'           => null,
                    'order_status'       => null,
                    'triggered_by'       => null,
                    'created_at'         => null,
                    'total'              => 0,
                    'in_flight'          => 0,
                    'retrying'           => 0,
                    'bought'             => 0,
                    'skipped'            => 0,
                    'failed'             => 0,
                    'sells_open'         => 0,
                    'sells_pending'      => 0,
                    'completed'          => false,
                ],
            ]);
        }

        $counts = BotBuyExecution::query()
            ->where('bot_order_id', $order->id)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $pending  = (int) ($counts[BotBuyExecution::STATUS_PENDING] ?? 0);
        $buying   = (int) ($counts[BotBuyExecution::STATUS_BUYING] ?? 0);
        $bought   = (int) ($counts[BotBuyExecution::STATUS_BOUGHT] ?? 0);
        $skipped  = (int) ($counts[BotBuyExecution::STATUS_SKIPPED] ?? 0);
        $failed   = (int) ($counts[BotBuyExecution::STATUS_FAILED] ?? 0);
        $total    = $pending + $buying + $bought + $skipped + $failed;
        $inFlight = $pending + $buying;

        // BUYING rows that already failed at least one attempt (network
        // hiccup, fill-confirmation timeout, etc.) but are still being
        // retried automatically by BuyExecutionJob — no user action needed.
        $retrying = BotBuyExecution::query()
            ->where('bot_order_id', $order->id)
            ->where('status', BotBuyExecution::STATUS_BUYING)
            ->whereNotNull('failure_reason')
            ->count();

        // BOUGHT rows whose OpenSellOrdersJob hasn't run yet (it's dispatched
        // onto a separate queue right after the buy fills, so there's a real
        // gap between "buy done" and "sell tiers placed"). Once that job runs
        // the execution either gains sell orders or flips to FAILED, so this
        // reaches 0 as soon as every bought execution has been through it.
        $sellsPending = BotBuyExecution::query()
            ->where('bot_order_id', $order->id)
            ->where('status', BotBuyExecution::STATUS_BOUGHT)
            ->whereDoesntHave('sellOrders')
            ->count();

        $sellsOpen = BotSellOrder::query()
            ->join('bot_buy_executions', 'bot_buy_executions.id', '=', 'bot_sell_orders.bot_buy_execution_id')
            ->where('bot_buy_executions.bot_order_id', $order->id)
            ->where('bot_sell_orders.status', BotSellOrder::STATUS_OPEN)
            ->count();

        return response()->json([
            'data' => [
                'auto_trade_enabled' => $autoTradeEnabled,
                'has_order'          => true,
                'order_id'           => (int) $order->id,
                'order_status'       => $order->status,
                'triggered_by'       => $order->triggered_by,
                'created_at'         => $order->created_at?->toIso8601String(),
                'total'              => $total,
                'in_flight'          => $inFlight,
                'retrying'           => $retrying,
                'bought'             => $bought,
                'skipped'            => $skipped,
                'failed'             => $failed,
                'sells_open'         => $sellsOpen,
                'sells_pending'      => $sellsPending,
                'completed'          => $total > 0 && $inFlight === 0 && $sellsPending === 0,
            ],
        ]);
    }

    public function cancelPreview(int $id): JsonResponse
    {
        $order = $this->findOwnedOrder($id);
        $breakdown = $this->cancelService->preview($order);

        return response()->json(['data' => $breakdown]);
    }

    public function cancel(int $id): JsonResponse
    {
        $order  = $this->findOwnedOrder($id);
        $result = $this->cancelService->cancel($order);

        return response()->json([
            'message' => 'سفارش‌های باز ربات لغو شدند.',
            'data'    => $result,
        ]);
    }

    private function findOwnedOrder(int $id): BotOrder
    {
        $order = BotOrder::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (! $order) {
            throw new NotFoundHttpException('Bot order not found.');
        }

        return $order;
    }
}
