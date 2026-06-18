@extends('dashboard.layout.master')

@section('title', 'جزئیات سفارش ربات #' . $botOrder->id)

@section('content')

    <div class="row">
        <div class="col-12">

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">سفارش #{{ $botOrder->id }}</h4>
                    <a href="{{ route('admin.bot.order.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-right me-1"></i> بازگشت به لیست
                    </a>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <th class="text-muted" style="width:40%">Batch UUID</th>
                                    <td><code>{{ $botOrder->batch_uuid }}</code></td>
                                </tr>
                                <tr>
                                    <th class="text-muted">کاربر</th>
                                    <td>
                                        {{ $botOrder->user?->email }}
                                        <small class="d-block text-muted">{{ $botOrder->user?->mobile }}</small>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted">مبلغ کل (USDT)</th>
                                    <td>{{ number_format($botOrder->total_amount_usdt, 8) }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">آلفا (snapshot)</th>
                                    <td>{{ $botOrder->alpha_snapshot }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <th class="text-muted" style="width:40%">وضعیت</th>
                                    <td>
                                        @php
                                            $badgeClass = match ($botOrder->status) {
                                                'FILLED' => 'bg-success',
                                                'PENDING' => 'bg-warning text-dark',
                                                'PARTIALLY_FILLED' => 'bg-info',
                                                'CANCELED' => 'bg-danger',
                                                default => 'bg-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $botOrder->status }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-muted">تریگر</th>
                                    <td>{{ $botOrder->triggered_by }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">تاریخ ایجاد</th>
                                    <td>{{ $botOrder->created_at?->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">تکمیل‌شده در</th>
                                    <td>{{ $botOrder->completed_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Buy Executions --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">اجراهای خرید ({{ $botOrder->buyExecutions->count() }} ارز)</h5>
                </div>
                <div class="card-body">
                    @forelse ($botOrder->buyExecutions as $execution)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong>{{ $execution->currency?->symbol }}</strong>
                                    — {{ $execution->currency?->name }}
                                </div>
                                @php
                                    $execBadge = match ($execution->status) {
                                        'BOUGHT' => 'bg-success',
                                        'PENDING' => 'bg-warning text-dark',
                                        'FAILED' => 'bg-danger',
                                        'SKIPPED' => 'bg-secondary',
                                        default => 'bg-secondary',
                                    };
                                    $execSymbol       = $execution->currency?->symbol;
                                    $execMarket       = $execSymbol ? ($markets[$execSymbol] ?? null) : null;
                                    $execCurrentPrice = $execSymbol ? cache("market:price:{$execSymbol}USDT") : null;
                                @endphp
                                <span class="badge {{ $execBadge }}">{{ $execution->status }}</span>
                            </div>

                            <div class="row text-sm">
                                <div class="col-md-3">
                                    <small class="text-muted d-block">تخصیص (USDT)</small>
                                    <strong>{{ number_format($execution->allocated_usdt, 2) }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">مقدار خریده‌شده</small>
                                    <strong>{{ number_format($execution->filled_amount, 8) }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">میانگین قیمت خرید</small>
                                    <strong>{{ $execution->avg_buy_price ? number_format($execution->avg_buy_price, 8) : '—' }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">کارمزد صرافی</small>
                                    <strong>{{ number_format($execution->exchange_fee, 8) }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">تارگت‌ها</small>
                                    @php
                                        $orig = $execution->original_sell_orders_count;
                                        $eff = $execution->effective_sell_orders_count;
                                    @endphp
                                    @if ($orig === null && $eff === null)
                                        <strong>—</strong>
                                    @elseif ($orig !== null && $eff !== null && $eff != $orig)
                                        <span class="badge bg-warning text-dark">{{ $eff }}/{{ $orig }} (Collapsed)</span>
                                    @else
                                        <strong>{{ $eff ?? $orig }}/{{ $orig }}</strong>
                                    @endif
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">قیمت لحظه‌ای (USDT)</small>
                                    <strong class="font-number live-price-display"
                                            data-live-price="{{ $execSymbol }}"
                                            data-market-id="{{ $execMarket?->id ?? '' }}">
                                        {{ $execCurrentPrice ? number_format((float) $execCurrentPrice, 2) : '—' }}
                                    </strong>
                                </div>
                            </div>

                            {{-- Sell orders --}}
                            @if ($execution->sellOrders->isNotEmpty())
                                <div class="mt-3">
                                    <h6 class="fw-semibold mb-2" style="font-size:.85rem; color:#5a5a72;">
                                        <i class="fas fa-arrow-down me-1 text-danger"></i> سفارشات فروش
                                        <small class="text-muted fw-normal ms-2">(کلیک روی سطر برای جزئیات تسویه)</small>
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover table-bordered mb-0 align-middle">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th class="text-center" style="width:15%">هدف</th>
                                                    <th class="text-center" style="width:15%">قیمت هدف (USDT)</th>
                                                    <th class="text-center" style="width:14%">فاصله تا هدف</th>
                                                    <th class="text-center" style="width:8%">سهم (%)</th>
                                                    <th class="text-center" style="width:14%">مقدار فروش</th>
                                                    <th class="text-center" style="width:10%">وضعیت</th>
                                                    <th class="text-center" style="width:14%">تکمیل در</th>
                                                    <th class="text-center" style="width:4%"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($execution->sellOrders as $sell)
                                                    @php
                                                        $sellBadge = match ($sell->status) {
                                                            'FILLED'   => 'bg-success',
                                                            'OPEN'     => 'bg-primary',
                                                            'CANCELED' => 'bg-danger',
                                                            default    => 'bg-secondary',
                                                        };
                                                        $targetPrice = $execution->avg_buy_price
                                                            ? (float) $execution->avg_buy_price + ((float) $sell->target_value / 100 * (float) $execution->avg_buy_price)
                                                            : null;
                                                        $hasSettlement = $sell->settlement !== null;
                                                        $distancePct   = null;
                                                        if ($targetPrice && $execCurrentPrice && $sell->status === 'OPEN') {
                                                            $distancePct = (($targetPrice - (float) $execCurrentPrice) / (float) $execCurrentPrice) * 100;
                                                        }
                                                    @endphp
                                                    <tr class="sell-main-row"
                                                        data-sell-id="{{ $sell->id }}"
                                                        data-status="{{ $sell->status }}"
                                                        style="{{ $hasSettlement ? 'cursor:pointer;' : '' }}">
                                                        <td class="text-center">
                                                            <span class="fw-semibold">{{ formatNumberTrimZeros($sell->target_value, 8) }}</span>
                                                            <small class="text-muted d-block" style="font-size:.75rem">{{ $sell->target_type }}</small>
                                                        </td>
                                                        <td class="text-center font-number">
                                                            {{ $targetPrice ? number_format($targetPrice, 2) : '—' }}
                                                        </td>
                                                        <td class="text-center distance-cell"
                                                            @if($sell->status === 'OPEN') data-target="{{ $targetPrice }}" data-symbol="{{ $execSymbol }}" @endif>
                                                            @if ($sell->status === 'FILLED')
                                                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>تکمیل شد</span>
                                                            @elseif ($sell->status === 'CANCELED')
                                                                <span class="text-muted">—</span>
                                                            @elseif ($distancePct !== null)
                                                                @if ($distancePct > 0)
                                                                    <span class="badge bg-warning text-dark font-number">{{ number_format($distancePct, 2) }}٪ تا هدف</span>
                                                                @else
                                                                    <span class="badge bg-success font-number">{{ number_format(abs($distancePct), 2) }}٪ بالاتر</span>
                                                                @endif
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">{{ $sell->share_percent }}٪</td>
                                                        <td class="text-center font-number">{{ formatNumberTrimZeros($sell->amount_to_sell, 8) }}</td>
                                                        <td class="text-center">
                                                            <span class="badge {{ $sellBadge }}">{{ $sell->status }}</span>
                                                        </td>
                                                        <td class="text-center">
                                                            <small>{{ $sell->filled_at?->format('Y-m-d H:i') ?? '—' }}</small>
                                                        </td>
                                                        <td class="text-center">
                                                            @if ($hasSettlement)
                                                                <i class="fas fa-chevron-down toggle-icon text-muted"
                                                                   id="icon-{{ $sell->id }}"
                                                                   style="font-size:.7rem; transition:transform .2s"></i>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @if ($hasSettlement)
                                                        <tr class="settlement-detail d-none" id="settlement-{{ $sell->id }}">
                                                            <td colspan="8" class="p-3 bg-light">
                                                                <div class="row g-2 text-center">
                                                                    <div class="col-6 col-md-3">
                                                                        <div class="p-2 rounded border bg-white">
                                                                            <small class="text-muted d-block">درآمد ناخالص (gross_revenue)</small>
                                                                            <strong class="font-number text-primary">{{ formatNumberTrimZeros($sell->settlement->gross_revenue, 4) }}</strong>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6 col-md-3">
                                                                        <div class="p-2 rounded border bg-white">
                                                                            <small class="text-muted d-block">بهای تمام‌شده (cost_basis)</small>
                                                                            <strong class="font-number">{{ formatNumberTrimZeros($sell->settlement->cost_basis, 4) }}</strong>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6 col-md-3">
                                                                        <div class="p-2 rounded border bg-white">
                                                                            <small class="text-muted d-block">کارمزد شبکه (network_fee)</small>
                                                                            <strong class="font-number text-warning">{{ formatNumberTrimZeros($sell->settlement->network_fee, 4) }}</strong>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6 col-md-3">
                                                                        <div class="p-2 rounded border bg-white">
                                                                            <small class="text-muted d-block">کارمزد صرافی (exchange_fee)</small>
                                                                            <strong class="font-number text-warning">{{ formatNumberTrimZeros($sell->settlement->exchange_fee, 4) }}</strong>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6 col-md-3">
                                                                        <div class="p-2 rounded border bg-white">
                                                                            <small class="text-muted d-block">کارمزد عملکرد (performance_fee)</small>
                                                                            <strong class="font-number text-warning">{{ formatNumberTrimZeros($sell->settlement->performance_fee, 4) }}</strong>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6 col-md-3">
                                                                        <div class="p-2 rounded border bg-white">
                                                                            <small class="text-muted d-block">کارمزد لغو (cancel_fee)</small>
                                                                            <strong class="font-number">{{ formatNumberTrimZeros($sell->settlement->cancel_fee, 4) }}</strong>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6 col-md-3">
                                                                        <div class="p-2 rounded border bg-white">
                                                                            <small class="text-muted d-block">سود/زیان خالص (net_pnl)</small>
                                                                            <strong class="font-number {{ (float) $sell->settlement->net_pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                                                                {{ formatNumberTrimZeros($sell->settlement->net_pnl, 4) }}
                                                                            </strong>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6 col-md-3">
                                                                        <div class="p-2 rounded border bg-white">
                                                                            <small class="text-muted d-block">تسویه در</small>
                                                                            <strong>{{ $sell->settlement->settled_at?->format('Y-m-d H:i') ?? '—' }}</strong>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted text-center py-3">هیچ اجرای خریدی ثبت نشده است.</p>
                    @endforelse
                </div>
            </div>

            {{-- Trade Settlements (cancel & fill fee breakdown) --}}
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">تفکیک تسویه‌ها و کارمزدها</h5>
                    <span class="badge bg-secondary">{{ $settlements->count() }} ردیف</span>
                </div>
                <div class="card-body">
                    @if ($settlements->isEmpty())
                        <p class="text-muted text-center py-3 mb-0">هنوز هیچ تسویه‌ای برای این سفارش ثبت نشده است.</p>
                    @else
                        @php
                            $sumGross = $settlements->sum(fn($s) => (float) $s->gross_revenue);
                            $sumCost = $settlements->sum(fn($s) => (float) $s->cost_basis);
                            $sumNet = $settlements->sum(fn($s) => (float) $s->network_fee);
                            $sumExch = $settlements->sum(fn($s) => (float) $s->exchange_fee);
                            $sumPerf = $settlements->sum(fn($s) => (float) $s->performance_fee);
                            $sumCancel = $settlements->sum(fn($s) => (float) $s->cancel_fee);
                            $sumPnl = $settlements->sum(fn($s) => (float) $s->net_pnl);
                        @endphp

                        <div class="row mb-3 g-2">
                            <div class="col-md-3">
                                <div class="p-2 border rounded text-center"><small class="text-muted d-block">مجموع
                                        gross_revenue</small><strong>{{ number_format($sumGross, 4) }}</strong></div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded text-center"><small class="text-muted d-block">مجموع
                                        cost_basis</small><strong>{{ number_format($sumCost, 4) }}</strong></div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded text-center"><small class="text-muted d-block">کارمزد
                                        شبکه</small><strong class="text-warning">{{ number_format($sumNet, 4) }}</strong></div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded text-center"><small class="text-muted d-block">کارمزد
                                        صرافی</small><strong class="text-warning">{{ number_format($sumExch, 4) }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded text-center"><small class="text-muted d-block">کارمزد
                                        عملکرد
                                        (۲۲٪)</small><strong class="text-warning">{{ number_format($sumPerf, 4) }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded text-center"><small class="text-muted d-block">cancel_fee
                                        (قدیمی)</small><strong>{{ number_format($sumCancel, 4) }}</strong></div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded text-center"><small class="text-muted d-block">net_pnl
                                        کل</small><strong
                                        class="{{ $sumPnl >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($sumPnl, 4) }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded text-center"><small class="text-muted d-block">مبلغ
                                        بازگشتی
                                        برآوردی</small><strong
                                        class="text-primary">{{ number_format($sumCost + $sumPnl, 4) }}</strong></div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>زمان</th>
                                        <th>کوین</th>
                                        <th>وضعیت sell</th>
                                        <th>gross_revenue</th>
                                        <th>cost_basis</th>
                                        <th>network_fee</th>
                                        <th>exchange_fee</th>
                                        <th>performance_fee</th>
                                        <th>cancel_fee</th>
                                        <th>net_pnl</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($settlements as $s)
                                        <tr>
                                            <td><small>{{ $s->settled_at?->format('Y-m-d H:i:s') ?? '—' }}</small></td>
                                            <td>
                                                <strong>{{ $s->buyExecution?->currency?->symbol ?? '—' }}</strong>
                                                <small class="d-block text-muted">{{ $s->buyExecution?->currency?->name }}</small>
                                            </td>
                                            <td>
                                                @php
                                                    $st = $s->sellOrder?->status;
                                                    $stBadge = match ($st) {
                                                        'FILLED' => 'bg-success',
                                                        'OPEN' => 'bg-primary',
                                                        'CANCELED' => 'bg-danger',
                                                        default => 'bg-secondary',
                                                    };
                                                @endphp
                                                <span class="badge {{ $stBadge }}">{{ $st ?? '—' }}</span>
                                            </td>
                                            <td>{{ number_format($s->gross_revenue, 8) }}</td>
                                            <td>{{ number_format($s->cost_basis, 8) }}</td>
                                            <td class="text-warning">{{ number_format($s->network_fee, 8) }}</td>
                                            <td class="text-warning">{{ number_format($s->exchange_fee, 8) }}</td>
                                            <td class="text-warning">{{ number_format($s->performance_fee, 8) }}</td>
                                            <td>{{ number_format($s->cancel_fee, 8) }}</td>
                                            <td class="{{ (float) $s->net_pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                                <strong>{{ number_format($s->net_pnl, 8) }}</strong>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

@endsection

@section('vendor-script')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Expandable settlement rows ────────────────────────────────────────
    document.querySelectorAll('.sell-main-row[data-sell-id]').forEach(function (row) {
        var sellId    = row.dataset.sellId;
        var detailRow = document.getElementById('settlement-' + sellId);
        if (!detailRow) return;

        row.addEventListener('click', function () {
            var icon = document.getElementById('icon-' + sellId);
            detailRow.classList.toggle('d-none');
            if (icon) {
                icon.style.transform = detailRow.classList.contains('d-none')
                    ? 'rotate(0deg)'
                    : 'rotate(180deg)';
            }
        });
    });

    // ── Live price via WebSocket ──────────────────────────────────────────
    if (typeof window.Echo === 'undefined') {
        console.warn('Echo not initialised – live prices disabled.');
        return;
    }

    var subscribedMarkets = new Set();

    document.querySelectorAll('.live-price-display[data-market-id]').forEach(function (el) {
        var marketId = el.dataset.marketId;
        var symbol   = el.dataset.livePrice;
        if (!marketId || subscribedMarkets.has(marketId)) return;
        subscribedMarkets.add(marketId);

        window.Echo.channel('market.' + marketId).listen('MarketUpdated', function (event) {
            var rawPrice = event.last;
            if (rawPrice === undefined || rawPrice === null) return;
            var price = parseFloat(String(rawPrice).replace(/,/g, ''));
            if (!isFinite(price) || price <= 0) return;

            // Update header live-price display for this symbol
            document.querySelectorAll('.live-price-display[data-live-price="' + symbol + '"]').forEach(function (badge) {
                badge.textContent = price.toLocaleString('en-US', { maximumFractionDigits: 8 });
            });

            // Recalculate "distance to target" for OPEN rows of this symbol
            document.querySelectorAll('.distance-cell[data-symbol="' + symbol + '"][data-target]').forEach(function (cell) {
                var targetPrice = parseFloat(cell.dataset.target);
                if (!isFinite(targetPrice)) return;

                var pct = ((targetPrice - price) / price) * 100;
                if (pct > 0) {
                    cell.innerHTML = '<span class="badge bg-warning text-dark font-number">' + pct.toFixed(2) + '٪ تا هدف</span>';
                } else {
                    cell.innerHTML = '<span class="badge bg-success font-number">' + Math.abs(pct).toFixed(2) + '٪ بالاتر</span>';
                }
            });
        });
    });
});
</script>
@endsection
