<?php

namespace App\Http\Controllers\Bot\V1;

use App\Http\Controllers\Controller;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
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
