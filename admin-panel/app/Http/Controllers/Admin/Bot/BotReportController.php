<?php

namespace App\Http\Controllers\Admin\Bot;

use App\Enums\TransactionSubTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotTradeSettlement;
use App\Models\Currency;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BotReportController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to, $currencyId] = $this->filters($request);

        $settlementBase = $this->settlementQuery($from, $to, $currencyId);

        $kpi = [
            'gross_revenue'   => (clone $settlementBase)->sum('gross_revenue'),
            'cost_basis'      => (clone $settlementBase)->sum('cost_basis'),
            'net_pnl'         => (clone $settlementBase)->sum('net_pnl'),
            'performance_fee' => (clone $settlementBase)->sum('performance_fee'),
            'cancel_fee'      => (clone $settlementBase)->sum('cancel_fee'),
            'exchange_fee'    => (clone $settlementBase)->sum('exchange_fee'),
            'network_fee'     => (clone $settlementBase)->sum('network_fee'),
            'spread_fee'      => (clone $settlementBase)->sum('spread_fee'),
        ];

        $execBase = $this->executionQuery($from, $to, $currencyId);

        $kpi['bought_volume']   = (clone $execBase)->where('status', 'BOUGHT')->sum('allocated_usdt');
        $kpi['skipped_count']   = (clone $execBase)->where('status', 'SKIPPED')->count();
        $kpi['collapsed_count'] = (clone $execBase)
            ->whereNotNull('effective_sell_orders_count')
            ->whereNotNull('original_sell_orders_count')
            ->whereColumn('effective_sell_orders_count', '<', 'original_sell_orders_count')
            ->count();

        $kpi['transfer_fee'] = (string) Transaction::query()
            ->where('subtype', TransactionSubTypeEnum::BOT_TRANSFER_FEE)
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->sum('amount');

        $kpi['active_users'] = (clone $execBase)
            ->join('bot_orders', 'bot_orders.id', '=', 'bot_buy_executions.bot_order_id')
            ->distinct('bot_orders.user_id')
            ->count('bot_orders.user_id');

        $perCoin = $this->perCoinBreakdown($from, $to, $currencyId);

        $settlements = $this->settlementQuery($from, $to, $currencyId)
            ->with(['buyExecution.currency', 'sellOrder', 'user'])
            ->orderByDesc('settled_at')
            ->paginate(50)
            ->withQueryString();

        $currencies = Currency::orderBy('symbol')->get(['id', 'symbol']);

        return view('dashboard.bot.reports.index', compact(
            'kpi', 'perCoin', 'settlements', 'currencies', 'from', 'to', 'currencyId'
        ));
    }

    public function exportOrders(Request $request): StreamedResponse
    {
        [$from, $to, $currencyId] = $this->filters($request);

        $query = BotOrder::query()->with('user')->orderBy('id')
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->when($currencyId, fn ($q) => $q->whereHas('buyExecutions', fn ($e) => $e->where('currency_id', $currencyId)));

        $headings = ['ID', 'UUID', 'کاربر', 'مبلغ کل (USDT)', 'آلفا', 'وضعیت', 'منشأ', 'تاریخ ایجاد', 'تاریخ تکمیل'];

        return $this->streamCsv('bot_orders', $headings, function ($write) use ($query) {
            $query->chunk(1000, function ($chunk) use ($write) {
                foreach ($chunk as $o) {
                    $write([
                        $o->id,
                        $o->batch_uuid,
                        $o->user?->email ?? $o->user?->mobile ?? 'N/A',
                        $o->total_amount_usdt,
                        $o->alpha_snapshot,
                        $o->status,
                        $o->triggered_by,
                        optional($o->created_at)->format('Y-m-d H:i:s'),
                        optional($o->completed_at)->format('Y-m-d H:i:s'),
                    ]);
                }
            });
        });
    }

    public function exportExecutions(Request $request): StreamedResponse
    {
        [$from, $to, $currencyId] = $this->filters($request);

        $query = $this->executionQuery($from, $to, $currencyId)
            ->with(['currency', 'botOrder.user'])
            ->orderBy('bot_buy_executions.id');

        $headings = [
            'ID', 'Order ID', 'کاربر', 'ارز', 'تخصیص (USDT)', 'مقدار خریداری‌شده',
            'میانگین قیمت خرید', 'کارمزد صرافی (خرید)', 'کارمزد شبکه', 'وضعیت',
            'تعداد فروش اولیه', 'تعداد فروش مؤثر', 'Collapsed', 'علت شکست/توضیح', 'تاریخ',
        ];

        return $this->streamCsv('bot_executions', $headings, function ($write) use ($query) {
            $query->chunk(1000, function ($chunk) use ($write) {
                foreach ($chunk as $e) {
                    $orig = $e->original_sell_orders_count;
                    $eff  = $e->effective_sell_orders_count;
                    $collapsed = ($orig !== null && $eff !== null && $eff < $orig) ? 'بله' : 'خیر';
                    $write([
                        $e->id,
                        $e->bot_order_id,
                        $e->botOrder?->user?->email ?? $e->botOrder?->user?->mobile ?? 'N/A',
                        $e->currency?->symbol ?? $e->currency_id,
                        $e->allocated_usdt,
                        $e->filled_amount,
                        $e->avg_buy_price,
                        $e->buy_ref_exchange_fee,
                        $e->network_fee,
                        $e->status,
                        $orig,
                        $eff,
                        $collapsed,
                        $e->failure_reason,
                        optional($e->created_at)->format('Y-m-d H:i:s'),
                    ]);
                }
            });
        });
    }

    public function exportSettlements(Request $request): StreamedResponse
    {
        [$from, $to, $currencyId] = $this->filters($request);

        $query = $this->settlementQuery($from, $to, $currencyId)
            ->with(['buyExecution.currency', 'user'])
            ->orderBy('id');

        $headings = [
            'ID', 'کاربر', 'ارز', 'درآمد ناخالص', ' (Cost Basis)بهای تمام‌شده', 'کارمزد شبکه',
            'کارمزد صرافی (خرید+فروش)', 'اسپرد', 'کارمزد عملکرد', 'کارمزد لغو', 'سود/زیان خالص', 'تاریخ تسویه',
        ];

        return $this->streamCsv('bot_settlements', $headings, function ($write) use ($query) {
            $query->chunk(1000, function ($chunk) use ($write) {
                foreach ($chunk as $s) {
                    $write([
                        $s->id,
                        $s->user?->email ?? $s->user?->mobile ?? 'N/A',
                        $s->buyExecution?->currency?->symbol ?? 'N/A',
                        $s->gross_revenue,
                        $s->cost_basis,
                        $s->network_fee,
                        $s->exchange_fee,
                        $s->spread_fee,
                        $s->performance_fee,
                        $s->cancel_fee,
                        $s->net_pnl,
                        optional($s->settled_at)->format('Y-m-d H:i:s'),
                    ]);
                }
            });
        });
    }

    /**
     * @return array{0:?string,1:?string,2:?int}
     */
    private function filters(Request $request): array
    {
        $from = $request->filled('from') ? $request->get('from') : null;
        $to   = $request->filled('to') ? $request->get('to') : null;
        $currencyId = $request->filled('currency_id') ? (int) $request->get('currency_id') : null;

        return [$from, $to, $currencyId];
    }

    private function settlementQuery(?string $from, ?string $to, ?int $currencyId)
    {
        return BotTradeSettlement::query()
            ->when($from, fn ($q) => $q->whereDate('settled_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('settled_at', '<=', $to))
            ->when($currencyId, fn ($q) => $q->whereHas('buyExecution', fn ($e) => $e->where('currency_id', $currencyId)));
    }

    private function executionQuery(?string $from, ?string $to, ?int $currencyId)
    {
        return BotBuyExecution::query()
            ->when($from, fn ($q) => $q->whereDate('bot_buy_executions.created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('bot_buy_executions.created_at', '<=', $to))
            ->when($currencyId, fn ($q) => $q->where('bot_buy_executions.currency_id', $currencyId));
    }

    /**
     * Per-currency breakdown merging buy-execution aggregates (volume, skipped,
     * collapsed) with realized PnL/fees from settlements.
     */
    private function perCoinBreakdown(?string $from, ?string $to, ?int $currencyId)
    {
        $execAgg = $this->executionQuery($from, $to, $currencyId)
            ->select('bot_buy_executions.currency_id')
            ->selectRaw("SUM(CASE WHEN status = 'BOUGHT' THEN allocated_usdt ELSE 0 END) as bought_volume")
            ->selectRaw("SUM(CASE WHEN status = 'BOUGHT' THEN filled_amount ELSE 0 END) as bought_amount")
            ->selectRaw("SUM(CASE WHEN status = 'SKIPPED' THEN 1 ELSE 0 END) as skipped_count")
            ->selectRaw('SUM(CASE WHEN effective_sell_orders_count IS NOT NULL AND original_sell_orders_count IS NOT NULL AND effective_sell_orders_count < original_sell_orders_count THEN 1 ELSE 0 END) as collapsed_count')
            ->groupBy('bot_buy_executions.currency_id')
            ->with('currency:id,symbol,logo')
            ->get()
            ->keyBy('currency_id');

        $pnlAgg = BotTradeSettlement::query()
            ->select('bot_buy_executions.currency_id')
            ->selectRaw('SUM(bot_trade_settlements.net_pnl) as net_pnl')
            ->selectRaw('SUM(bot_trade_settlements.performance_fee) as performance_fee')
            ->selectRaw('SUM(bot_trade_settlements.cancel_fee) as cancel_fee')
            ->join('bot_buy_executions', 'bot_buy_executions.id', '=', 'bot_trade_settlements.bot_buy_execution_id')
            ->when($from, fn ($q) => $q->whereDate('bot_trade_settlements.settled_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('bot_trade_settlements.settled_at', '<=', $to))
            ->when($currencyId, fn ($q) => $q->where('bot_buy_executions.currency_id', $currencyId))
            ->groupBy('bot_buy_executions.currency_id')
            ->get()
            ->keyBy('currency_id');

        $currencyIds = $execAgg->keys()->merge($pnlAgg->keys())->unique();

        return $currencyIds->map(function ($cid) use ($execAgg, $pnlAgg) {
            $exec = $execAgg->get($cid);
            $pnl  = $pnlAgg->get($cid);

            return [
                'currency_id'     => $cid,
                'symbol'          => $exec?->currency?->symbol,
                'logo'            => $exec?->currency?->coinLogo(),
                'bought_volume'   => (string) ($exec->bought_volume ?? '0'),
                'bought_amount'   => (string) ($exec->bought_amount ?? '0'),
                'skipped_count'   => (int) ($exec->skipped_count ?? 0),
                'collapsed_count' => (int) ($exec->collapsed_count ?? 0),
                'net_pnl'         => (string) ($pnl->net_pnl ?? '0'),
                'performance_fee' => (string) ($pnl->performance_fee ?? '0'),
                'cancel_fee'      => (string) ($pnl->cancel_fee ?? '0'),
            ];
        })->sortByDesc('bought_volume')->values();
    }

    private function streamCsv(string $prefix, array $headings, callable $rows): StreamedResponse
    {
        $filename = $prefix . '_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($headings, $rows) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM so Excel renders Persian headers correctly.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headings);
            $rows(function (array $row) use ($handle) {
                fputcsv($handle, $row);
            });
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
