<?php

namespace App\Http\Controllers\Bot\V1;

use App\Http\Controllers\Controller;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotUserSettings;
use App\Services\Bot\BotOrderCancelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BotOrderController extends Controller
{
    public function __construct(private readonly BotOrderCancelService $cancelService) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) min(100, max(1, (int) $request->query('per_page', 15)));
        $status  = $request->query('status');

        $query = BotOrder::query()
            ->where('user_id', Auth::id())
            ->orderByDesc('id');

        if ($status) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

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

    public function show(int $id): JsonResponse
    {
        $order = BotOrder::with([
            'buyExecutions.currency:id,symbol,name,persian_name,logo',
            'buyExecutions.sellOrders',
        ])
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (! $order) {
            throw new NotFoundHttpException('Bot order not found.');
        }

        $logoBase = config('bitexroom.currency_logo_base_url');

        $buyExecutions = $order->buyExecutions->map(function ($exec) use ($logoBase) {
            $currency = $exec->currency;

            $sellOrders = $exec->sellOrders->map(fn ($sell) => [
                'id'                   => $sell->id,
                'exchange_order_id'    => $sell->exchange_order_id,
                'target_type'          => $sell->target_type,
                'target_value'         => $sell->target_value,
                'share_percent'        => $sell->share_percent,
                'amount_to_sell'       => $sell->amount_to_sell,
                'status'               => $sell->status,
                'filled_at'            => $sell->filled_at,
            ])->values();

            return [
                'id'                         => $exec->id,
                'currency_id'                => $exec->currency_id,
                'currency_symbol'            => $currency?->symbol,
                'currency_name'              => $currency?->name,
                'currency_persian_name'      => $currency?->persian_name,
                'currency_logo'              => $currency?->logo ? $logoBase . '/' . $currency->logo : null,
                'allocated_usdt'             => $exec->allocated_usdt,
                'filled_amount'              => $exec->filled_amount,
                'avg_buy_price'              => $exec->avg_buy_price,
                'exchange_fee'               => $exec->exchange_fee,
                'network_fee'                => $exec->network_fee,
                'original_sell_orders_count' => $exec->original_sell_orders_count,
                'effective_sell_orders_count'=> $exec->effective_sell_orders_count,
                'status'                     => $exec->status,
                'failure_reason'             => $exec->failure_reason,
                'created_at'                 => $exec->created_at,
                'sell_orders'                => $sellOrders,
            ];
        })->values();

        return response()->json([
            'data' => [
                'id'                 => $order->id,
                'batch_uuid'         => $order->batch_uuid,
                'total_amount_usdt'  => $order->total_amount_usdt,
                'status'             => $order->status,
                'triggered_by'       => $order->triggered_by,
                'completed_at'       => $order->completed_at,
                'created_at'         => $order->created_at,
                'buy_executions'     => $buyExecutions,
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
