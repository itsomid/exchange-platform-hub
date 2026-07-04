@extends('dashboard.layout.master')

@section('title', 'جزئیات سفارش ربات #' . $botOrder->id)

@section('content')

    <div class="row">
        <div class="col-12">

            <div class="card mb-4">
                @php
                    $badgeClass = match ($botOrder->status) {
                        'FILLED' => 'bg-success',
                        'PENDING' => 'bg-warning text-dark',
                        'PARTIALLY_FILLED' => 'bg-info',
                        'CANCELED' => 'bg-danger',
                        default => 'bg-secondary',
                    };
                    $otherUsdt = max(0, $totalInvested - $freedUsdt - $lockedUsdt);
                    $otherPct = max(0, 100 - $freedPct - $lockedPct);
                @endphp

                {{-- Order identity header --}}
                <div class="card-header"
                    style="background: var(--bs-card-bg, #fff); border-bottom: 1px solid rgba(0,0,0,.08);">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                <h4 class="mb-0">سفارش #{{ $botOrder->id }}</h4>
                                <span class="badge {{ $badgeClass }} px-3"
                                    style="font-size:.85rem">{{ $botOrder->status }}</span>
                                <span class="badge bg-secondary">{{ $botOrder->triggered_by }}</span>
                            </div>
                            <div class="small text-muted d-flex flex-wrap align-items-center gap-2">
                                <span><i class="fas fa-user fa-xs me-1"></i>{{ $botOrder->user?->email }}</span>
                                <span class="opacity-50">|</span>
                                <span>{{ $botOrder->user?->mobile }}</span>
                                <span class="opacity-50">|</span>
                                <code class="small">{{ $botOrder->batch_uuid }}</code>
                            </div>
                            <div class="small text-muted mt-1 d-flex flex-wrap align-items-center gap-2">
                                <span><i class="fas fa-calendar-alt fa-xs me-1"></i>ایجاد:
                                    {{ $botOrder->created_at?->format('Y-m-d H:i') }}</span>
                                @if ($botOrder->completed_at)
                                    <span class="opacity-50">|</span>
                                    <span>تکمیل: {{ $botOrder->completed_at->format('Y-m-d H:i') }}</span>
                                @endif
                                <span class="opacity-50">|</span>
                                <span>آلفا: <strong>{{ $botOrder->alpha_snapshot }}</strong></span>
                            </div>
                        </div>
                        <a href="{{ $botOrder->user_id ? route('admin.bot.order.user', $botOrder->user_id) : route('admin.bot.order.index') }}"
                            class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-right me-1"></i> بازگشت به کاربر
                        </a>
                    </div>
                </div>

                {{-- Capital summary --}}
                <div class="card-body">
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="border rounded p-3 text-center h-100">
                                <small class="text-muted d-block mb-1"><i class="fas fa-coins fa-xs me-1"></i>سرمایه
                                    اختصاص‌یافته</small>
                                <div class="fw-bold fs-5 font-number">{{ formatNumberTrimZeros($totalInvested) }}</div>
                                <small class="text-muted">USDT</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border border-success rounded p-3 text-center h-100"
                                style="background:rgba(40,199,111,.06)">
                                <small class="text-muted d-block mb-1"><i
                                        class="fas fa-unlock fa-xs me-1 text-success"></i>آزادشده (برگشتی)</small>
                                <div class="fw-bold fs-5 font-number text-success">{{ formatNumberTrimZeros($freedUsdt) }}
                                </div>
                                <small class="text-muted">USDT</small>
                                <div class="mt-1">
                                    <span class="badge text-bg-success font-number"
                                        style="font-size:.75rem">{{ formatNumberTrimZeros($freedPct, 2) }}٪ از کل</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border border-warning rounded p-3 text-center h-100"
                                style="background:rgba(255,159,67,.06)">
                                <small class="text-muted d-block mb-1"><i
                                        class="fas fa-lock fa-xs me-1 text-warning"></i>قفل‌شده (در انتظار فروش)</small>
                                <div class="fw-bold fs-5 font-number text-warning">{{ formatNumberTrimZeros($lockedUsdt) }}
                                </div>
                                <small class="text-muted">USDT</small>
                                <div class="mt-1">
                                    <span class="badge text-bg-warning font-number"
                                        style="font-size:.75rem">{{ formatNumberTrimZeros($lockedPct, 2) }}٪ از کل</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border rounded p-3 text-center h-100
                                {{ $totalPnl > 0 ? 'border-success' : ($totalPnl < 0 ? 'border-danger' : '') }}"
                                style="background:{{ $totalPnl > 0 ? 'rgba(40,199,111,.06)' : ($totalPnl < 0 ? 'rgba(234,84,85,.06)' : '') }}">
                                <small class="text-muted d-block mb-1"><i class="fas fa-chart-line fa-xs me-1"></i>سود /
                                    زیان خالص (P&L)</small>
                                <div
                                    class="fw-bold fs-5 font-number {{ $totalPnl >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ formatNumberTrimZeros($totalPnl) }}{{ $totalPnl >= 0 ? '+' : '' }}
                                </div>
                                <small class="text-muted">USDT</small>
                                <div class="mt-1 d-flex justify-content-center gap-2">
                                    <span class="badge bg-success bg-opacity-25 text-success font-number"
                                        style="font-size:.72rem">▲ {{ formatNumberTrimZeros($positivePnl) }}</span>
                                    <span class="badge bg-danger bg-opacity-25 text-danger font-number"
                                        style="font-size:.72rem">▼ {{ formatNumberTrimZeros($negativePnl) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Capital allocation progress bar --}}
                    <div>
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>توزیع سرمایه</span>
                            <span class="font-number">{{ formatNumberTrimZeros($totalInvested) }} USDT</span>
                        </div>
                        <div class="progress rounded" style="height:22px;">
                            <div class="progress-bar bg-success fw-semibold" role="progressbar"
                                style="width:{{ $freedPct }}%; font-size:.78rem;"
                                title="آزادشده: {{ formatNumberTrimZeros($freedUsdt) }} USDT">
                                @if ($freedPct >= 7)
                                    {{ formatNumberTrimZeros($freedPct, 2) }}٪
                                @endif
                            </div>
                            <div class="progress-bar bg-warning text-dark fw-semibold" role="progressbar"
                                style="width:{{ $lockedPct }}%; font-size:.78rem;"
                                title="قفل: {{ formatNumberTrimZeros($lockedUsdt) }} USDT">
                                @if ($lockedPct >= 7)
                                    {{ formatNumberTrimZeros($lockedPct, 2) }}٪
                                @endif
                            </div>
                            @if ($otherPct > 0.1)
                                <div class="progress-bar bg-secondary" role="progressbar"
                                    style="width:{{ $otherPct }}%; font-size:.78rem; opacity:.45;"
                                    title="سایر: {{ formatNumberTrimZeros($otherUsdt) }} USDT">
                                    @if ($otherPct >= 7)
                                        {{ formatNumberTrimZeros($otherPct) }}٪
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="d-flex flex-wrap gap-3 mt-2 small">
                            <span>
                                <span class="badge bg-success me-1">آزادشده</span>
                                <span class="font-number">{{ formatNumberTrimZeros($freedUsdt) }} USDT</span>
                            </span>
                            <span>
                                <span class="badge bg-warning text-dark me-1">قفل‌شده</span>
                                <span class="font-number">{{ formatNumberTrimZeros($lockedUsdt) }} USDT</span>
                            </span>
                            @if ($otherUsdt > 0.005)
                                <span>
                                    <span class="badge bg-secondary me-1">سایر / تخصیص‌نیافته</span>
                                    <span class="font-number">{{ formatNumberTrimZeros($otherUsdt) }} USDT</span>
                                </span>
                            @endif
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
                                    $execSymbol = $execution->currency?->symbol;
                                    $execMarket = $execSymbol ? $markets[$execSymbol] ?? null : null;
                                    $execCurrentPrice = $execSymbol ? cache("market:price:{$execSymbol}USDT") : null;
                                @endphp
                                <span class="badge {{ $execBadge }}">{{ $execution->status }}</span>
                            </div>

                            <div class="row text-sm gap-3">
                                <div class="col-md-3 col-xxl-2">
                                    <small class="text-muted d-block mb-1">تخصیص (USDT)</small>
                                    <strong>{{ formatNumberTrimZeros($execution->allocated_usdt, 2) }}</strong>
                                </div>
                                <div class="col-md-3 col-xxl-2">
                                    <small class="text-muted d-block mb-1">مقدار خریده‌شده</small>
                                    <strong>{{ formatNumberTrimZeros($execution->filled_amount) }}</strong>
                                </div>
                                <div class="col-md-3 col-xxl-2">
                                    <small class="text-muted d-block mb-1">میانگین قیمت خرید</small>
                                    <strong>{{ $execution->avg_buy_price ? formatNumberTrimZeros($execution->avg_buy_price) : '—' }}</strong>
                                </div>
                                <div class="col-md-3 col-xxl-2">
                                    <small class="text-muted d-block mb-1">کارمزد صرافی خرید</small>
                                    <strong>{{ formatNumberTrimZeros($execution->buy_ref_exchange_fee, 8) }}</strong>
                                </div>
                                <div class="col-md-3 col-xxl-2">
                                    <small class="text-muted d-block mb-1">تارگت‌ها</small>
                                    @php
                                        $orig = $execution->original_sell_orders_count;
                                        $eff = $execution->effective_sell_orders_count;
                                    @endphp
                                    @if ($orig === null && $eff === null)
                                        <strong>—</strong>
                                    @elseif ($orig !== null && $eff !== null && $eff != $orig)
                                        <span class="badge bg-warning text-dark">{{ $eff }}/{{ $orig }}
                                            (Collapsed)</span>
                                    @else
                                        <strong>{{ $eff ?? $orig }}/{{ $orig }}</strong>
                                    @endif
                                </div>
                                <div class="col-md-3 col-xxl-2">
                                    <small class="text-muted d-block mb-1">قیمت لحظه‌ای (USDT)</small>
                                    <strong class="font-number live-price-display" data-live-price="{{ $execSymbol }}"
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
                                                            'FILLED' => 'bg-success',
                                                            'OPEN' => 'bg-primary',
                                                            'CANCELED' => 'bg-danger',
                                                            default => 'bg-secondary',
                                                        };
                                                        $targetPrice = $execution->avg_buy_price
                                                            ? (float) $execution->avg_buy_price +
                                                                ((float) $sell->target_value / 100) *
                                                                    (float) $execution->avg_buy_price
                                                            : null;
                                                        $hasSettlement = $sell->settlement !== null;
                                                        $distancePct = null;
                                                        if (
                                                            $targetPrice &&
                                                            $execCurrentPrice &&
                                                            $sell->status === 'OPEN'
                                                        ) {
                                                            $distancePct =
                                                                (($targetPrice - (float) $execCurrentPrice) /
                                                                    (float) $execCurrentPrice) *
                                                                100;
                                                        }
                                                    @endphp
                                                    <tr class="sell-main-row" data-sell-id="{{ $sell->id }}"
                                                        data-status="{{ $sell->status }}"
                                                        style="{{ $hasSettlement ? 'cursor:pointer;' : '' }}">
                                                        <td class="text-center">
                                                            <span
                                                                class="fw-semibold">{{ formatNumberTrimZeros($sell->target_value, 8) }}</span>
                                                            <small class="text-muted d-block"
                                                                style="font-size:.75rem">{{ $sell->target_type }}</small>
                                                        </td>
                                                        <td class="text-center font-number">
                                                            {{ $targetPrice ? number_format($targetPrice, 2) : '—' }}
                                                        </td>
                                                        <td class="text-center distance-cell"
                                                            @if ($sell->status === 'OPEN') data-target="{{ $targetPrice }}" data-symbol="{{ $execSymbol }}" @endif>
                                                            @if ($sell->status === 'FILLED')
                                                                <span class="badge bg-success"><i
                                                                        class="fas fa-check me-1"></i>تکمیل شد</span>
                                                            @elseif ($sell->status === 'CANCELED')
                                                                <span class="text-muted">—</span>
                                                            @elseif ($distancePct !== null)
                                                                @if ($distancePct > 0)
                                                                    <span
                                                                        class="badge bg-warning text-dark font-number">{{ number_format($distancePct, 2) }}٪
                                                                        تا هدف</span>
                                                                @else
                                                                    <span
                                                                        class="badge bg-success font-number">{{ number_format(abs($distancePct), 2) }}٪
                                                                        بالاتر</span>
                                                                @endif
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">{{ $sell->share_percent }}٪</td>
                                                        <td class="text-center font-number">
                                                            {{ formatNumberTrimZeros($sell->amount_to_sell, 8) }}</td>
                                                        <td class="text-center">
                                                            <span
                                                                class="badge {{ $sellBadge }}">{{ $sell->status }}</span>
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
                                                        @php
                                                            $stepBuyFeeShare = 0;
                                                            if (
                                                                (float) $execution->filled_amount > 0 &&
                                                                (float) $execution->buy_ref_exchange_fee > 0
                                                            ) {
                                                                $stepBuyFeeShare =
                                                                    ((float) $execution->buy_ref_exchange_fee *
                                                                        (float) $sell->amount_to_sell) /
                                                                    (float) $execution->filled_amount;
                                                            }
                                                            // sell_ref_exchange_fee = fee charged by exchange for THIS sell step (stored on sell order)
                                                            $stepSellExchangeFee =
                                                                (float) ($sell->sell_ref_exchange_fee ?? 0);
                                                            // settlement->exchange_fee is already buy_share + sell_fee (combined by SettlementService)
                                                            $stepTotalExchangeFee =
                                                                (float) $sell->settlement->exchange_fee;
                                                        @endphp
                                                        <tr class="settlement-detail d-none"
                                                            id="settlement-{{ $sell->id }}">
                                                            <td colspan="8" class="p-3 bg-light">
                                                                <div class="row g-2 text-center">
                                                                    <div class="col-6 col-md-3">
                                                                        <div class="p-2 rounded border bg-white">
                                                                            <small class="text-muted d-block">درآمد ناخالص
                                                                                (gross_revenue)</small>
                                                                            <strong
                                                                                class="font-number text-primary">{{ formatNumberTrimZeros($sell->settlement->gross_revenue) }}</strong>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6 col-md-3">
                                                                        <div class="p-2 rounded border bg-white">
                                                                            <small class="text-muted d-block">بهای تمام‌شده
                                                                                (cost_basis)</small>
                                                                            <strong
                                                                                class="font-number">{{ formatNumberTrimZeros($sell->settlement->cost_basis) }}</strong>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6 col-md-3">
                                                                        <div class="p-2 rounded border bg-white">
                                                                            <small class="text-muted d-block">سود و زیان
                                                                                ناخالص (gross_pnl)</small>
                                                                            <strong
                                                                                class="font-number text-warning">{{ formatNumberTrimZeros($sell->settlement->gross_revenue - $sell->settlement->cost_basis) }}</strong>
                                                                        </div>
                                                                    </div>


                                                                    <div class="col-6 col-md-3">
                                                                        <div class="p-2 rounded border bg-white">
                                                                            <small class="text-muted d-block">کارمزد صرافی
                                                                                مرجع (خرید+فروش)</small>
                                                                            <strong
                                                                                class="font-number text-warning">{{ formatNumberTrimZeros($stepTotalExchangeFee) }}</strong>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6 col-md-3">
                                                                        <div class="p-2 rounded border bg-white">
                                                                            <small class="text-muted d-block">سهم کارمزد
                                                                                خرید این پله</small>
                                                                            <strong
                                                                                class="font-number text-warning">{{ formatNumberTrimZeros($stepBuyFeeShare) }}</strong>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6 col-md-3">
                                                                        <div class="p-2 rounded border bg-white">
                                                                            <small class="text-muted d-block">کارمزد فروش
                                                                                این پله</small>
                                                                            <strong
                                                                                class="font-number text-warning">{{ formatNumberTrimZeros($stepSellExchangeFee) }}</strong>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6 col-md-3">
                                                                        <div class="p-2 rounded border bg-white">
                                                                            <small class="text-muted d-block">کارمزد عملکرد
                                                                                (performance_fee)</small>
                                                                            <strong
                                                                                class="font-number text-warning">{{ formatNumberTrimZeros($sell->settlement->performance_fee) }}</strong>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6 col-md-3">
                                                                        <div class="p-2 rounded border bg-white">
                                                                            <small class="text-muted d-block">کارمزد لغو
                                                                                (cancel_fee)</small>
                                                                            <strong
                                                                                class="font-number">{{ formatNumberTrimZeros($sell->settlement->cancel_fee) }}</strong>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6 col-md-3">
                                                                        <div class="p-2 rounded border bg-white">
                                                                            <small class="text-muted d-block">کارمزد شبکه
                                                                                (network_fee)</small>
                                                                            <strong
                                                                                class="font-number text-warning">{{ formatNumberTrimZeros($sell->settlement->network_fee) }}</strong>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6 col-md-3">
                                                                        <div class="p-2 rounded border bg-white">
                                                                            <small class="text-muted d-block">سود/زیان خالص
                                                                                (net_pnl)</small>
                                                                            <strong
                                                                                class="font-number {{ (float) $sell->settlement->net_pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                                                                {{ formatNumberTrimZeros($sell->settlement->net_pnl) }}
                                                                            </strong>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-6 col-md-3">
                                                                        <div class="p-2 rounded border bg-white">
                                                                            <small class="text-muted d-block">تسویه
                                                                                در</small>
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
                                        شبکه</small><strong class="text-warning">{{ number_format($sumNet, 4) }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded text-center"><small class="text-muted d-block">کارمزد
                                        صرافی (خرید+فروش)</small><strong
                                        class="text-warning">{{ number_format($sumExch, 4) }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded text-center"><small class="text-muted d-block">کارمزد
                                        عملکرد
                                        (۲۲٪)</small><strong
                                        class="text-warning">{{ number_format($sumPerf, 4) }}</strong>
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
                                        <th>exchange_fee (buy+sell)</th>
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
                                                <small
                                                    class="d-block text-muted">{{ $s->buyExecution?->currency?->name }}</small>
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
        document.addEventListener('DOMContentLoaded', function() {

            // ── Expandable settlement rows ────────────────────────────────────────
            document.querySelectorAll('.sell-main-row[data-sell-id]').forEach(function(row) {
                var sellId = row.dataset.sellId;
                var detailRow = document.getElementById('settlement-' + sellId);
                if (!detailRow) return;

                row.addEventListener('click', function() {
                    var icon = document.getElementById('icon-' + sellId);
                    detailRow.classList.toggle('d-none');
                    if (icon) {
                        icon.style.transform = detailRow.classList.contains('d-none') ?
                            'rotate(0deg)' :
                            'rotate(180deg)';
                    }
                });
            });

            // ── Live price via WebSocket ──────────────────────────────────────────
            if (typeof window.Echo === 'undefined') {
                console.warn('Echo not initialised – live prices disabled.');
                return;
            }

            var subscribedMarkets = new Set();

            document.querySelectorAll('.live-price-display[data-market-id]').forEach(function(el) {
                var marketId = el.dataset.marketId;
                var symbol = el.dataset.livePrice;
                if (!marketId || subscribedMarkets.has(marketId)) return;
                subscribedMarkets.add(marketId);

                window.Echo.channel('market.' + marketId).listen('MarketUpdated', function(event) {
                    var rawPrice = event.last;
                    if (rawPrice === undefined || rawPrice === null) return;
                    var price = parseFloat(String(rawPrice).replace(/,/g, ''));
                    if (!isFinite(price) || price <= 0) return;

                    // Update header live-price display for this symbol
                    document.querySelectorAll('.live-price-display[data-live-price="' + symbol +
                        '"]').forEach(function(badge) {
                        badge.textContent = price.toLocaleString('en-US', {
                            maximumFractionDigits: 8
                        });
                    });

                    // Recalculate "distance to target" for OPEN rows of this symbol
                    document.querySelectorAll('.distance-cell[data-symbol="' + symbol +
                        '"][data-target]').forEach(function(cell) {
                        var targetPrice = parseFloat(cell.dataset.target);
                        if (!isFinite(targetPrice)) return;

                        var pct = ((targetPrice - price) / price) * 100;
                        if (pct > 0) {
                            cell.innerHTML =
                                '<span class="badge bg-warning text-dark font-number">' +
                                pct.toFixed(2) + '٪ تا هدف</span>';
                        } else {
                            cell.innerHTML = '<span class="badge bg-success font-number">' +
                                Math.abs(pct).toFixed(2) + '٪ بالاتر</span>';
                        }
                    });
                });
            });
        });
    </script>
@endsection
