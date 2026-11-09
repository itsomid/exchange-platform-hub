<?php

namespace App\Http\Controllers\Admin\Bot;

use App\Enums\TransactionStatusEnum;
use App\Helpers\DateFormatter;
use App\Http\Controllers\Controller;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotTradeSettlement;
use App\Models\Bot\BotWalletTransfer;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Morilog\Jalali\Jalalian;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BotReportController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to, $currencyId, $fromInput, $toInput, $defaultWeek] = $this->filters($request);

        $settings = BotGlobalSettings::current();
        $currencies = Currency::orderBy('symbol')->get(['id', 'name', 'persian_name', 'symbol', 'logo']);

        // One grouped pass per table. Headline totals are the sum of those groups,
        // so the page does not scan executions once for KPIs, again per coin, and
        // again for the user count's sibling aggregate.
        $execRows = $this->executionAggregates($from, $to, $currencyId);
        $pnlRows = $this->settlementAggregates($from, $to, $currencyId);

        $kpi = [
            'gross_revenue'    => $this->sumMoney($pnlRows, 'gross_revenue'),
            'cost_basis'       => $this->sumMoney($pnlRows, 'cost_basis'),
            'net_pnl'          => $this->sumMoney($pnlRows, 'net_pnl'),
            'performance_fee'  => $this->sumMoney($pnlRows, 'performance_fee'),
            'referral_fee'     => $this->sumMoney($pnlRows, 'referral_fee'),
            'cancel_fee'       => $this->sumMoney($pnlRows, 'cancel_fee'),
            'exchange_fee'     => $this->sumMoney($pnlRows, 'exchange_fee'),
            'network_fee'      => $this->sumMoney($pnlRows, 'network_fee'),
            'winning_pnl'      => $this->sumMoney($pnlRows, 'winning_pnl'),
            'losing_pnl'       => $this->sumMoney($pnlRows, 'losing_pnl'),
            'winning_count'    => $this->sumInt($pnlRows, 'winning_count'),
            'losing_count'     => $this->sumInt($pnlRows, 'losing_count'),
            'settlement_count' => $this->sumInt($pnlRows, 'settlement_count'),
            'bought_volume'    => $this->sumMoney($execRows, 'bought_volume'),
            'bought_count'     => $this->sumInt($execRows, 'bought_count'),
            'skipped_count'    => $this->sumInt($execRows, 'skipped_count'),
            'failed_count'     => $this->sumInt($execRows, 'failed_count'),
            'collapsed_count'  => $this->sumInt($execRows, 'collapsed_count'),
        ];

        // The fee lives on the small transfer table. The ledger copy has no
        // currency, so a coin filter cannot narrow it.
        $kpi['transfer_fee'] = $currencyId ? null : (string) BotWalletTransfer::query()
            ->where('status', TransactionStatusEnum::SUCCESS)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->sum('fee');

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

        $kpi['active_users'] = $this->activeUsers($from, $to, $currencyId);
        $perCoin = $this->presentCoinRows($execRows, $pnlRows, $currencies);

        $settlements = $this->settlementQuery($from, $to, $currencyId)
            ->with([
                'buyExecution:id,currency_id',
                'buyExecution.currency:id,symbol,name',
                'user:id,email,mobile',
            ])
            ->orderByDesc('settled_at')
            ->paginate(50)
            ->withQueryString();

        return view('dashboard.bot.reports.index', compact(
            'kpi', 'perCoin', 'settlements', 'currencies', 'currencyId', 'settings', 'defaultWeek'
        ))->with([
            'from' => $fromInput,
            'to' => $toInput,
        ]);
    }

    public function exportOrders(Request $request): StreamedResponse
    {
        [$from, $to, $currencyId] = $this->filters($request);

        $query = BotOrder::query()->with('user')->orderBy('id')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
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
     * The page always has a bounded range. An empty request is the last seven
     * days, so the aggregates never scan the whole history.
     *
     * @return array{0: Carbon, 1: Carbon, 2: ?int, 3: string, 4: string, 5: bool}
     */
    private function filters(Request $request): array
    {
        $defaultFrom = now()->subDays(6)->startOfDay();
        $defaultTo = now()->endOfDay();

        $from = $this->parseJalaliDay($request->get('from'), true) ?? $defaultFrom;
        $to = $this->parseJalaliDay($request->get('to'), false) ?? $defaultTo;

        if ($from->greaterThan($to)) {
            $from = $defaultFrom;
            $to = $defaultTo;
        }

        $currencyId = $request->filled('currency_id') ? (int) $request->get('currency_id') : null;
        $defaultWeek = $from->equalTo($defaultFrom) && $to->equalTo($defaultTo);

        return [
            $from,
            $to,
            $currencyId,
            Jalalian::fromCarbon($from)->format('Y/m/d'),
            Jalalian::fromCarbon($to)->format('Y/m/d'),
            $defaultWeek,
        ];
    }

    private function parseJalaliDay(mixed $value, bool $startOfDay): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            $parsed = Carbon::parse(DateFormatter::convertPersianToCarbonDate(trim($value)));
        } catch (\Throwable) {
            return null;
        }

        return $startOfDay ? $parsed->startOfDay() : $parsed->endOfDay();
    }

    private function settlementQuery(Carbon $from, Carbon $to, ?int $currencyId)
    {
        return BotTradeSettlement::query()
            ->where('settled_at', '>=', $from)
            ->where('settled_at', '<=', $to)
            ->when($currencyId, fn ($q) => $q->whereHas('buyExecution', fn ($e) => $e->where('currency_id', $currencyId)));
    }

    private function executionQuery(Carbon $from, Carbon $to, ?int $currencyId)
    {
        return BotBuyExecution::query()
            ->where('bot_buy_executions.created_at', '>=', $from)
            ->where('bot_buy_executions.created_at', '<=', $to)
            ->when($currencyId, fn ($q) => $q->where('bot_buy_executions.currency_id', $currencyId));
    }

    private function executionAggregates(Carbon $from, Carbon $to, ?int $currencyId): Collection
    {
        return DB::table('bot_buy_executions')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->when($currencyId, fn ($q) => $q->where('currency_id', $currencyId))
            ->select('currency_id')
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('BOUGHT','CLOSED') THEN allocated_usdt ELSE 0 END),0) as bought_volume")
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('BOUGHT','CLOSED') THEN filled_amount ELSE 0 END),0) as bought_amount")
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('BOUGHT','CLOSED') THEN 1 ELSE 0 END),0) as bought_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'SKIPPED' THEN 1 ELSE 0 END),0) as skipped_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'FAILED' THEN 1 ELSE 0 END),0) as failed_count")
            ->selectRaw('COALESCE(SUM(CASE WHEN effective_sell_orders_count IS NOT NULL AND original_sell_orders_count IS NOT NULL AND effective_sell_orders_count < original_sell_orders_count THEN 1 ELSE 0 END),0) as collapsed_count')
            ->groupBy('currency_id')
            ->get();
    }

    private function settlementAggregates(Carbon $from, Carbon $to, ?int $currencyId): Collection
    {
        return DB::table('bot_trade_settlements')
            ->join('bot_buy_executions', 'bot_buy_executions.id', '=', 'bot_trade_settlements.bot_buy_execution_id')
            ->where('bot_trade_settlements.settled_at', '>=', $from)
            ->where('bot_trade_settlements.settled_at', '<=', $to)
            ->when($currencyId, fn ($q) => $q->where('bot_buy_executions.currency_id', $currencyId))
            ->select('bot_buy_executions.currency_id')
            ->selectRaw('COALESCE(SUM(bot_trade_settlements.gross_revenue),0) as gross_revenue')
            ->selectRaw('COALESCE(SUM(bot_trade_settlements.cost_basis),0) as cost_basis')
            ->selectRaw('COALESCE(SUM(bot_trade_settlements.net_pnl),0) as net_pnl')
            ->selectRaw('COALESCE(SUM(bot_trade_settlements.performance_fee),0) as performance_fee')
            ->selectRaw('COALESCE(SUM(bot_trade_settlements.referral_fee),0) as referral_fee')
            ->selectRaw('COALESCE(SUM(bot_trade_settlements.cancel_fee),0) as cancel_fee')
            ->selectRaw('COALESCE(SUM(bot_trade_settlements.exchange_fee),0) as exchange_fee')
            ->selectRaw('COALESCE(SUM(bot_trade_settlements.network_fee),0) as network_fee')
            ->selectRaw('COALESCE(SUM(CASE WHEN bot_trade_settlements.net_pnl > 0 THEN bot_trade_settlements.net_pnl ELSE 0 END),0) as winning_pnl')
            ->selectRaw('COALESCE(SUM(CASE WHEN bot_trade_settlements.net_pnl < 0 THEN bot_trade_settlements.net_pnl ELSE 0 END),0) as losing_pnl')
            ->selectRaw('COALESCE(SUM(CASE WHEN bot_trade_settlements.net_pnl > 0 THEN 1 ELSE 0 END),0) as winning_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN bot_trade_settlements.net_pnl < 0 THEN 1 ELSE 0 END),0) as losing_count')
            ->selectRaw('COUNT(*) as settlement_count')
            ->groupBy('bot_buy_executions.currency_id')
            ->get();
    }

    private function activeUsers(Carbon $from, Carbon $to, ?int $currencyId): int
    {
        return (int) DB::table('bot_buy_executions')
            ->join('bot_orders', 'bot_orders.id', '=', 'bot_buy_executions.bot_order_id')
            ->where('bot_buy_executions.created_at', '>=', $from)
            ->where('bot_buy_executions.created_at', '<=', $to)
            ->when($currencyId, fn ($q) => $q->where('bot_buy_executions.currency_id', $currencyId))
            ->distinct()
            ->count('bot_orders.user_id');
    }

    /**
     * Names come from the currencies table. A grouped execution row has no
     * currency relation, and a coin can have settlements without a buy in the
     * same window.
     */
    private function presentCoinRows(Collection $execRows, Collection $pnlRows, Collection $currencies): Collection
    {
        $byId = $currencies->keyBy(fn (Currency $currency) => (int) $currency->id);
        $execByCoin = $execRows->keyBy(fn ($row) => (int) $row->currency_id);
        $pnlByCoin = $pnlRows->keyBy(fn ($row) => (int) $row->currency_id);

        return $execByCoin->keys()->merge($pnlByCoin->keys())->unique()->map(function ($cid) use ($execByCoin, $pnlByCoin, $byId) {
            $exec = $execByCoin->get($cid);
            $pnl = $pnlByCoin->get($cid);
            $currency = $byId->get((int) $cid);

            return [
                'currency_id'     => (int) $cid,
                'symbol'          => $currency?->symbol,
                'name'            => $currency?->persian_name ?: $currency?->name,
                'logo'            => $currency?->coinLogo(),
                'bought_volume'   => (string) ($exec->bought_volume ?? '0'),
                'bought_amount'   => (string) ($exec->bought_amount ?? '0'),
                'skipped_count'   => (int) ($exec->skipped_count ?? 0),
                'collapsed_count' => (int) ($exec->collapsed_count ?? 0),
                'net_pnl'         => (string) ($pnl->net_pnl ?? '0'),
                'performance_fee' => (string) ($pnl->performance_fee ?? '0'),
                'cancel_fee'      => (string) ($pnl->cancel_fee ?? '0'),
            ];
        })->sortByDesc(fn (array $row) => (float) $row['bought_volume'])->values();
    }

    private function sumMoney(Collection $rows, string $column): string
    {
        return $rows->reduce(
            fn (string $carry, object $row) => bcadd($carry, (string) ($row->{$column} ?? '0'), 8),
            '0',
        );
    }

    private function sumInt(Collection $rows, string $column): int
    {
        return (int) $rows->sum(fn (object $row) => (int) ($row->{$column} ?? 0));
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
