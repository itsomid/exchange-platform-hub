<?php

namespace App\Http\Controllers\Admin\Bot;

use App\Enums\TransactionSubTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotGlobalSettings;
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

        $settings = BotGlobalSettings::current();

        // Winning/losing splits are needed to explain the headline numbers: the
        // performance fee is charged per profitable settlement, while net_pnl is a
        // net of winners and losers, so the two totals move independently.
        $totals = $this->settlementQuery($from, $to, $currencyId)
            ->selectRaw('COALESCE(SUM(gross_revenue),0) as gross_revenue')
            ->selectRaw('COALESCE(SUM(cost_basis),0) as cost_basis')
            ->selectRaw('COALESCE(SUM(net_pnl),0) as net_pnl')
            ->selectRaw('COALESCE(SUM(performance_fee),0) as performance_fee')
            ->selectRaw('COALESCE(SUM(referral_fee),0) as referral_fee')
            ->selectRaw('COALESCE(SUM(cancel_fee),0) as cancel_fee')
            ->selectRaw('COALESCE(SUM(exchange_fee),0) as exchange_fee')
            ->selectRaw('COALESCE(SUM(network_fee),0) as network_fee')
            ->selectRaw('COALESCE(SUM(CASE WHEN net_pnl > 0 THEN net_pnl ELSE 0 END),0) as winning_pnl')
            ->selectRaw('COALESCE(SUM(CASE WHEN net_pnl < 0 THEN net_pnl ELSE 0 END),0) as losing_pnl')
            ->selectRaw('COALESCE(SUM(CASE WHEN net_pnl > 0 THEN 1 ELSE 0 END),0) as winning_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN net_pnl < 0 THEN 1 ELSE 0 END),0) as losing_count')
            ->selectRaw('COUNT(*) as settlement_count')
            ->first();

        $kpi = [
            'gross_revenue'    => (string) $totals->gross_revenue,
            'cost_basis'       => (string) $totals->cost_basis,
            'net_pnl'          => (string) $totals->net_pnl,
            'performance_fee'  => (string) $totals->performance_fee,
            'referral_fee'     => (string) $totals->referral_fee,
            'cancel_fee'       => (string) $totals->cancel_fee,
            'exchange_fee'     => (string) $totals->exchange_fee,
            'network_fee'      => (string) $totals->network_fee,
            'winning_pnl'      => (string) $totals->winning_pnl,
            'losing_pnl'       => (string) $totals->losing_pnl,
            'winning_count'    => (int) $totals->winning_count,
            'losing_count'     => (int) $totals->losing_count,
            'settlement_count' => (int) $totals->settlement_count,
        ];

        $execTotals = $this->executionQuery($from, $to, $currencyId)
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('BOUGHT','CLOSED') THEN allocated_usdt ELSE 0 END),0) as bought_volume")
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('BOUGHT','CLOSED') THEN 1 ELSE 0 END),0) as bought_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'SKIPPED' THEN 1 ELSE 0 END),0) as skipped_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'FAILED' THEN 1 ELSE 0 END),0) as failed_count")
            ->selectRaw('COALESCE(SUM(CASE WHEN effective_sell_orders_count IS NOT NULL AND original_sell_orders_count IS NOT NULL AND effective_sell_orders_count < original_sell_orders_count THEN 1 ELSE 0 END),0) as collapsed_count')
            ->first();

        $kpi['bought_volume']   = (string) $execTotals->bought_volume;
        $kpi['bought_count']    = (int) $execTotals->bought_count;
        $kpi['skipped_count']   = (int) $execTotals->skipped_count;
        $kpi['failed_count']    = (int) $execTotals->failed_count;
        $kpi['collapsed_count'] = (int) $execTotals->collapsed_count;

        // Wallet transfer fees are ledgered per transaction and carry no currency of
        // their own, so a coin filter cannot narrow them. Expose null in that case
        // rather than a page-wide total that looks like it belongs to the coin.
        $kpi['transfer_fee'] = $currencyId ? null : (string) Transaction::query()
            ->where('subtype', TransactionSubTypeEnum::BOT_TRANSFER_FEE)
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->sum('amount');

        // What the platform actually keeps. Exchange and network fees are excluded:
        // they are passed straight through to the reference exchange and the chain.
        // The referral share is carved out of the performance fee, so it is deducted.
        $kpi['platform_revenue'] = bcsub(
            collect([
                $kpi['performance_fee'],
                $kpi['cancel_fee'],
                $kpi['transfer_fee'] ?? '0',
            ])->reduce(fn (string $carry, string $amount) => bcadd($carry, $amount, 8), '0'),
            $kpi['referral_fee'],
            8,
        );

        $execBase = $this->executionQuery($from, $to, $currencyId);

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
            'kpi', 'perCoin', 'settlements', 'currencies', 'from', 'to', 'currencyId', 'settings'
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
            'کارمزد صرافی (خرید+فروش)', 'کارمزد عملکرد', 'کارمزد لغو', 'سود/زیان خالص', 'تاریخ تسویه',
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
            ->selectRaw("SUM(CASE WHEN status IN ('BOUGHT','CLOSED') THEN allocated_usdt ELSE 0 END) as bought_volume")
            ->selectRaw("SUM(CASE WHEN status IN ('BOUGHT','CLOSED') THEN filled_amount ELSE 0 END) as bought_amount")
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
