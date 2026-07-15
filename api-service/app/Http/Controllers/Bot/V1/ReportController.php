<?php

namespace App\Http\Controllers\Bot\V1;

use App\Enums\TransactionSubTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\V1\ReportRequest;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotTradeSettlement;
use App\Models\Bot\BotWallet;
use App\Models\Transaction;
use App\Services\Bot\PriceFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ReportController extends Controller
{
    public function __construct(private readonly PriceFeed $priceFeed) {}

    /**
     * @OA\Get(
     *     path="/api/v1/bot/reports/summary",
     *     summary="Bot reports summary",
     *     description="Aggregated totals for the authenticated user's bot activity: deposit, withdrawal, realized profit, allocation usage and a daily PnL series.",
     *     tags={"Bot Reports"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="from", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to", in="query", required=false, @OA\Schema(type="string", format="date")),
     *
     *     @OA\Response(response=200, description="Summary retrieved successfully.")
     * )
     */
    public function summary(ReportRequest $request): JsonResponse
    {
        $userId = Auth::id();
        $from   = $request->validated('from');
        $to     = $request->validated('to');

        $deposit = (string) Transaction::query()
            ->where('user_id', $userId)
            ->where('subtype', TransactionSubTypeEnum::BOT_TRANSFER_IN)
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->sum('amount');

        $withdrawal = (string) Transaction::query()
            ->where('user_id', $userId)
            ->where('subtype', TransactionSubTypeEnum::BOT_TRANSFER_OUT)
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->sum('amount');

        $settlementsQuery = BotTradeSettlement::query()
            ->where('user_id', $userId)
            ->when($from, fn ($q) => $q->whereDate('settled_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('settled_at', '<=', $to));

        $profit = (string) (clone $settlementsQuery)->sum('net_pnl');

        $dailyPnl = (clone $settlementsQuery)
            ->selectRaw('DATE(settled_at) as date')
            ->selectRaw('SUM(net_pnl) as pnl')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'pnl'  => (string) $row->pnl,
            ])
            ->values();

        $wallet = BotWallet::where('user_id', $userId)->first();
        $allocationUsedPercent = '0';
        if ($wallet && (float) $wallet->balance > 0) {
            $allocationUsedPercent = bcmul(
                bcdiv((string) $wallet->locked_balance, (string) $wallet->balance, 10),
                '100',
                2
            );
        }

        return response()->json([
            'data' => [
                'deposit'                 => $deposit,
                'withdrawal'              => $withdrawal,
                'profit'                  => $profit,
                'allocation_used_percent' => $allocationUsedPercent,
                'daily_pnl'               => $dailyPnl,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/bot/reports/per-coin",
     *     summary="Bot reports per-coin breakdown",
     *     description="Per-currency aggregation of the authenticated user's bot holdings: total bought amount, weighted average buy price, current value and realized PnL.",
     *     tags={"Bot Reports"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="from", in="query", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to", in="query", required=false, @OA\Schema(type="string", format="date")),
     *
     *     @OA\Response(response=200, description="Per-coin report retrieved successfully.")
     * )
     */
    public function perCoin(ReportRequest $request): JsonResponse
    {
        $userId = Auth::id();
        $from   = $request->validated('from');
        $to     = $request->validated('to');

        // Bought executions aggregated per currency: total amount + weighted cost.
        $execRows = BotBuyExecution::query()
            ->select('bot_buy_executions.currency_id')
            ->selectRaw('SUM(bot_buy_executions.filled_amount) as total_amount')
            ->selectRaw('SUM(bot_buy_executions.filled_amount * bot_buy_executions.avg_buy_price) as cost_total')
            ->join('bot_orders', 'bot_orders.id', '=', 'bot_buy_executions.bot_order_id')
            ->where('bot_orders.user_id', $userId)
            ->where('bot_buy_executions.status', BotBuyExecution::STATUS_BOUGHT)
            ->when($from, fn ($q) => $q->whereDate('bot_buy_executions.created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('bot_buy_executions.created_at', '<=', $to))
            ->groupBy('bot_buy_executions.currency_id')
            ->with('currency:id,symbol,name,persian_name,logo')
            ->get();

        // Realized PnL per currency (from settlements joined to their buy execution).
        $realizedPnl = BotTradeSettlement::query()
            ->select('bot_buy_executions.currency_id')
            ->selectRaw('SUM(bot_trade_settlements.net_pnl) as realized_pnl')
            ->join('bot_buy_executions', 'bot_buy_executions.id', '=', 'bot_trade_settlements.bot_buy_execution_id')
            ->where('bot_trade_settlements.user_id', $userId)
            ->when($from, fn ($q) => $q->whereDate('bot_trade_settlements.settled_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('bot_trade_settlements.settled_at', '<=', $to))
            ->groupBy('bot_buy_executions.currency_id')
            ->pluck('realized_pnl', 'currency_id');

        // Total expected net profit across every (non-canceled) sell step, per currency,
        // assuming each step fills at its target price.
        $expectedByCurrency = $this->expectedProfitByCurrency($userId, $from, $to);

        $data = $execRows->map(function (BotBuyExecution $row) use ($realizedPnl, $expectedByCurrency) {
            $totalAmount = (string) $row->total_amount;
            $costTotal   = (string) $row->cost_total;

            $avgBuyPrice = bccomp($totalAmount, '0', 12) === 1
                ? bcdiv($costTotal, $totalAmount, 8)
                : '0';

            $currentValue = null;
            try {
                $livePrice    = $this->priceFeed->getLive((int) $row->currency_id);
                $currentValue = bcmul($totalAmount, (string) $livePrice, 16);
            } catch (Throwable $e) {
                $currentValue = null;
            }

            $realized = (string) ($realizedPnl[$row->currency_id] ?? '0');
            $expectedProfit = $expectedByCurrency[$row->currency_id] ?? '0';
            if (bccomp($expectedProfit, '0', 8) === 0) {
                $expectedProfit = '0';
            }

            return [
                'currency_id'   => $row->currency_id,
                'currency'      => $row->currency?->symbol,
                'name'          => $row->currency?->name,
                'persian_name'  => $row->currency?->persian_name,
                'currency_logo' => $row->currency?->logo ? config('bitexroom.currency_logo_base_url') . '/' . $row->currency->logo : null,
                'total_amount'  => $totalAmount,
                'avg_buy_price' => $avgBuyPrice,
                'cost_basis'    => $costTotal,
                'current_value'   => $currentValue,
                'expected_profit' => $expectedProfit,
                'realized_pnl'    => $realized,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    /**
     * Sum the expected net profit of every non-canceled sell step, grouped by currency.
     *
     * Each sell step (bot_sell_orders row) is assumed to fill at its target price. The net
     * estimate mirrors SettlementService: gross PnL minus the step's share of the buy-side
     * exchange fee, minus the performance fee on any positive result. The sell-side exchange
     * fee is unknown until the step fills, so it is omitted from the estimate.
     *
     * @return array<int|string, string> currency_id => total expected net profit (USDT)
     */
    private function expectedProfitByCurrency(int $userId, ?string $from, ?string $to): array
    {
        $performanceFeePercent = (string) BotGlobalSettings::current()->performance_fee_percent;

        $executions = BotBuyExecution::query()
            ->select('bot_buy_executions.*')
            ->join('bot_orders', 'bot_orders.id', '=', 'bot_buy_executions.bot_order_id')
            ->where('bot_orders.user_id', $userId)
            ->where('bot_buy_executions.status', BotBuyExecution::STATUS_BOUGHT)
            ->when($from, fn ($q) => $q->whereDate('bot_buy_executions.created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('bot_buy_executions.created_at', '<=', $to))
            ->with(['sellOrders' => fn ($q) => $q->where('status', '!=', BotSellOrder::STATUS_CANCELED)])
            ->get();

        $expected = [];
        foreach ($executions as $execution) {
            $avgBuyPrice  = (string) $execution->avg_buy_price;
            $filledAmount = (string) $execution->filled_amount;
            $buyFee       = (string) $execution->buy_ref_exchange_fee;

            foreach ($execution->sellOrders as $sell) {
                $amount     = (string) $sell->amount_to_sell;
                $targetPrice = $this->resolveTargetPrice(
                    $sell->target_type,
                    (string) $sell->target_value,
                    $avgBuyPrice,
                );

                $grossPnl = bcsub(
                    bcmul($amount, $targetPrice, 8),
                    bcmul($amount, $avgBuyPrice, 8),
                    8,
                );

                $buyFeeShare = bccomp($filledAmount, '0', 8) === 1
                    ? bcdiv(bcmul($buyFee, $amount, 8), $filledAmount, 8)
                    : '0';
                $pnlAfterFees = bcsub($grossPnl, $buyFeeShare, 8);

                $performanceFee = bccomp($pnlAfterFees, '0', 8) === 1
                    ? bcdiv(bcmul($pnlAfterFees, $performanceFeePercent, 8), '100', 8)
                    : '0';
                $netPnl = bcsub($pnlAfterFees, $performanceFee, 8);

                $cid = $execution->currency_id;
                $expected[$cid] = bcadd($expected[$cid] ?? '0', $netPnl, 8);
            }
        }

        return $expected;
    }

    /**
     * Resolve a sell step's absolute target price (mirrors OpenSellOrdersJob).
     */
    private function resolveTargetPrice(string $type, string $targetValue, string $avgBuyPrice): string
    {
        if ($type === 'price') {
            return $targetValue;
        }

        $factor = bcadd('1', bcdiv($targetValue, '100', 10), 10);

        return bcmul($avgBuyPrice, $factor, 8);
    }
}
