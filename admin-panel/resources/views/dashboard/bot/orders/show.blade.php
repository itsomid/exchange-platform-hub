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
                        'FAILED' => 'bg-danger',
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

                    {{-- ── Referral paid to the user's introducer for THIS order ── --}}
                    @if ($introducer)
                        <div class="border border-info rounded p-3 mb-4" style="background:rgba(3,195,236,.05)">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-user-friends text-info"></i>
                                    <span class="fw-semibold">پاداش معرف (رفرال ربات) — این سفارش</span>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-4">
                                    <div class="text-center">
                                        <small class="text-muted d-block mb-1">معرفِ کاربر</small>
                                        <div class="small">
                                            <i class="fas fa-user fa-xs me-1 text-info"></i>{{ $introducer->email ?? '—' }}
                                            (#{{ $introducer->id }})

                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <small class="text-muted d-block mb-1">پرداختی به معرف بابت این سفارش</small>
                                        <div class="fw-bold fs-6 font-number text-info">
                                            {{ formatNumberTrimZeros($referralPaid) }} <small
                                                class="text-muted">USDT</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

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

                    {{-- System description (read-only, auto-generated) --}}
                    @if ($botOrder->description)
                        <div class="mt-4 pt-3 border-top">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                <div>
                                    <h6 class="mb-1"><i class="fas fa-robot me-1 text-danger"></i>توضیحات سیستمی</h6>
                                    <small class="text-muted">ثبت خودکار توسط سیستم — قابل ویرایش نیست</small>
                                </div>
                                <button type="button"
                                    class="btn btn-sm btn-outline-danger js-view-order-system-description"
                                    data-order-id="{{ $botOrder->id }}">
                                    <i class="fas fa-expand me-1"></i> نمایش در Modal
                                </button>
                            </div>
                            <div id="system-description-html-{{ $botOrder->id }}">
                                @include('dashboard.bot.orders.partials.system-description-segments', [
                                    'description' => $botOrder->description,
                                ])
                            </div>
                        </div>
                    @endif

                    {{-- Admin notes (editable) --}}
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                            <div>
                                <h6 class="mb-1"><i class="fas fa-pen-to-square me-1 text-primary"></i>یادداشت ادمین
                                </h6>
                                <small class="text-muted">یادداشت داخلی ادمین برای پیگیری این سفارش</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary js-edit-order-admin-description"
                                data-order-id="{{ $botOrder->id }}"
                                data-update-url="{{ route('admin.bot.order.update-description', $botOrder) }}"
                                data-admin-description="{{ e($botOrder->admin_description ?? '') }}">
                                <i class="fas fa-pen me-1"></i> ویرایش
                            </button>
                        </div>
                        <div id="order-admin-description-display"
                            class="rounded-3 p-3 {{ $botOrder->admin_description ? '' : 'border border-dashed' }}"
                            style="background:{{ $botOrder->admin_description ? 'rgba(105,108,255,.05)' : 'rgba(0,0,0,.015)' }}; border-color:rgba(105,108,255,.18);">
                            @if ($botOrder->admin_description)
                                <div class="small text-break" style="white-space:pre-wrap;">
                                    {{ $botOrder->admin_description }}</div>
                            @else
                                <span class="text-muted small">هنوز یادداشت ادمینی ثبت نشده است.</span>
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
                                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                    <div class="rounded px-3 py-1 text-end"
                                        style="background:rgba(0,0,0,.03); border:1px solid rgba(0,0,0,.07)">
                                        <small class="text-muted d-block" style="font-size:.65rem; line-height:1.1">قیمت
                                            لحظه‌ای (USDT)</small>
                                        <strong class="font-number live-price-display d-block mb-0"
                                            style="font-size:1.15rem; line-height:1.3"
                                            data-live-price="{{ $execSymbol }}"
                                            data-market-id="{{ $execMarket?->id ?? '' }}"
                                            @if ($execCurrentPrice) data-prev-price="{{ $execCurrentPrice }}" @endif>
                                            {{ $execCurrentPrice ? formatNumberTrimZeros($execCurrentPrice) : '—' }}
                                        </strong>
                                    </div>
                                    <span class="badge {{ $execBadge }}">{{ $execution->status }}</span>
                                </div>
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
                                        @php
                                            $p2pMin = data_get(
                                                $execution->signal_snapshot,
                                                'effective_p2p_min_order_value',
                                            );
                                            $posValue =
                                                (float) $execution->filled_amount * (float) $execution->avg_buy_price;
                                            $avgTierValue = $orig > 0 ? $posValue / $orig : 0;
                                        @endphp
                                        <span class="d-inline-flex align-items-center gap-1">
                                            <span
                                                class="badge bg-warning text-dark">{{ $eff }}/{{ $orig }}
                                                (Collapsed)
                                            </span>
                                            <i class="fa-regular fa-info-circle text-warning" style="cursor:help"
                                                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
                                                title="<div class='text-end' style='min-width:250px;line-height:1.7'>
                                                    <div class='fw-bold mb-1'>چرا پله‌های فروش ادغام شد؟</div>
                                                    <div class='mb-1'>برای این ارز <b>{{ $orig }}</b> پله فروش تعریف شده بود، اما چون مقدار خریداری‌شده کم بود، اگر همان مقدار به <b>{{ $orig }}</b> پله تقسیم می‌شد ارزش هر پله کمتر از حداقل ارزش سفارش مجاز در صرافی مرجع (P2P) می‌شد. به همین دلیل ربات پله‌ها را ادغام کرد تا ارزش هر پله بالای حداقل بماند و در نهایت به <b>{{ $eff }}</b> پله رسید.</div>
                                                    <hr class='my-1'>
                                                    <div>کل ارزش موقعیت ≈ <b class='font-monospace'>{{ formatNumberTrimZeros($posValue, 2) }}</b> USDT</div>
                                                    <div>حداقل ارزش هر سفارش (P2P) = <b class='font-monospace'>{{ $p2pMin !== null ? formatNumberTrimZeros($p2pMin, 2) : '—' }}</b> USDT</div>
                                                    <div>ارزش تقریبی هر پله در حالت <b>{{ $orig }}</b>‌تایی ≈ <b class='font-monospace'>{{ formatNumberTrimZeros($avgTierValue, 2) }}</b> USDT (کمتر از حداقل)</div>
                                                </div>"></i>
                                        </span>
                                    @else
                                        <strong>{{ $eff ?? $orig }}/{{ $orig }}</strong>
                                    @endif
                                </div>
                            </div>

                            @include('dashboard.bot.orders.partials.execution-failure-alert', [
                                'execution' => $execution,
                            ])

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
                                                        $distanceAmount = null;
                                                        if (
                                                            $targetPrice &&
                                                            $execCurrentPrice &&
                                                            $sell->status === 'OPEN'
                                                        ) {
                                                            $distanceAmount =
                                                                (float) $targetPrice - (float) $execCurrentPrice;
                                                            $distancePct =
                                                                ($distanceAmount / (float) $execCurrentPrice) * 100;
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
                                                            {{ $targetPrice ? formatNumberTrimZeros($targetPrice) : '—' }}
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
                                                                    <div class="d-inline-flex align-items-center gap-2 px-2 py-1 rounded"
                                                                        style="background:rgba(255,159,67,.08); border:1px solid rgba(255,159,67,.28)">
                                                                        <span class="font-number fw-bold text-warning"
                                                                            style="font-size:.88rem; line-height:1.2; white-space:nowrap">
                                                                            {{ formatNumberTrimZeros($distanceAmount, 8) }}
                                                                            <small class="fw-normal text-muted"
                                                                                style="font-size:.7rem">USDT</small>
                                                                        </span>
                                                                        <span
                                                                            class="badge rounded-pill bg-warning text-dark font-number"
                                                                            style="font-size:.68rem">{{ formatNumberTrimZeros($distancePct, 2) }}٪</span>
                                                                        <span class="small fw-semibold text-warning"
                                                                            style="font-size:.72rem; white-space:nowrap">تا
                                                                            هدف</span>
                                                                    </div>
                                                                @else
                                                                    <div class="d-inline-flex align-items-center gap-2 px-2 py-1 rounded"
                                                                        style="background:rgba(40,199,111,.08); border:1px solid rgba(40,199,111,.28)">
                                                                        <span class="font-number fw-bold text-success"
                                                                            style="font-size:.88rem; line-height:1.2; white-space:nowrap">
                                                                            {{ formatNumberTrimZeros(abs($distanceAmount), 8) }}
                                                                            <small class="fw-normal text-muted"
                                                                                style="font-size:.7rem">USDT</small>
                                                                        </span>
                                                                        <span
                                                                            class="badge rounded-pill bg-success font-number"
                                                                            style="font-size:.68rem">{{ formatNumberTrimZeros(abs($distancePct), 2) }}٪</span>
                                                                        <span class="small fw-semibold text-success"
                                                                            style="font-size:.72rem; white-space:nowrap">بالاتر
                                                                            از هدف</span>
                                                                    </div>
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
                                                                        <div
                                                                            class="p-2 rounded border {{ (float) $sell->settlement->referral_fee > 0 ? 'border-info bg-white' : 'bg-white' }}">
                                                                            <small class="text-muted d-block">درآمد معرف
                                                                                (referral_fee)</small>
                                                                            <strong
                                                                                class="font-number text-info">{{ formatNumberTrimZeros($sell->settlement->referral_fee) }}</strong>
                                                                            @if ($introducer)
                                                                                <small class="d-block text-muted"
                                                                                    style="font-size:.68rem">به
                                                                                    {{ $introducer->email ?? $introducer->mobile }}</small>
                                                                            @endif
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
                            $sumReferral = $settlements->sum(fn($s) => (float) $s->referral_fee);
                            $sumCancel = $settlements->sum(fn($s) => (float) $s->cancel_fee);
                            $sumPnl = $settlements->sum(fn($s) => (float) $s->net_pnl);
                        @endphp

                        <div class="row mb-3 g-2">
                            <div class="col-md-3">
                                <div class="p-2 border rounded text-center"><small class="text-muted d-block">مجموع
                                        gross_revenue</small><strong>{{ formatNumberTrimZeros($sumGross) }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded text-center"><small class="text-muted d-block">مجموع
                                        cost_basis</small><strong>{{ formatNumberTrimZeros($sumCost) }}</strong></div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded text-center"><small class="text-muted d-block">کارمزد
                                        شبکه</small><strong
                                        class="text-warning">{{ formatNumberTrimZeros($sumNet) }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded text-center"><small class="text-muted d-block">کارمزد
                                        صرافی (خرید+فروش)</small><strong
                                        class="text-warning">{{ formatNumberTrimZeros($sumExch) }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded text-center"><small class="text-muted d-block">کارمزد
                                        عملکرد
                                        (۲۲٪)</small><strong
                                        class="text-warning">{{ formatNumberTrimZeros($sumPerf) }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded text-center"><small class="text-muted d-block">درآمد معرف
                                        (رفرال)</small><strong
                                        class="text-info">{{ formatNumberTrimZeros($sumReferral) }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded text-center"><small class="text-muted d-block">cancel_fee
                                        (قدیمی)</small><strong>{{ formatNumberTrimZeros($sumCancel) }}</strong></div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded text-center"><small class="text-muted d-block">net_pnl
                                        کل</small><strong
                                        class="{{ $sumPnl >= 0 ? 'text-success' : 'text-danger' }}">{{ formatNumberTrimZeros($sumPnl) }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-2 border rounded text-center"><small class="text-muted d-block">مبلغ
                                        بازگشتی
                                        برآوردی</small><strong
                                        class="text-primary">{{ formatNumberTrimZeros($sumCost + $sumPnl) }}</strong>
                                </div>
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
                                        <th>referral_fee</th>
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
                                            <td>{{ formatNumberTrimZeros($s->gross_revenue) }}</td>
                                            <td>{{ formatNumberTrimZeros($s->cost_basis) }}</td>
                                            <td class="text-warning">{{ formatNumberTrimZeros($s->network_fee) }}</td>
                                            <td class="text-warning">{{ formatNumberTrimZeros($s->exchange_fee) }}</td>
                                            <td class="text-warning">{{ formatNumberTrimZeros($s->performance_fee) }}
                                            </td>
                                            <td class="text-info">{{ formatNumberTrimZeros($s->referral_fee) }}</td>
                                            <td>{{ formatNumberTrimZeros($s->cancel_fee) }}</td>
                                            <td class="{{ (float) $s->net_pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                                <strong>{{ formatNumberTrimZeros($s->net_pnl) }}</strong>
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

    @include('dashboard.bot.orders.partials.description-modal')
    @include('dashboard.bot.orders.partials.system-description-modal')

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

            // ── Bootstrap tooltips (e.g. sell-target collapse explanation) ────────
            if (window.bootstrap && bootstrap.Tooltip) {
                document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
                    new bootstrap.Tooltip(el);
                });
            }

            // ── Live price via WebSocket ──────────────────────────────────────────
            if (typeof window.Echo === 'undefined') {
                console.warn('Echo not initialised – live prices disabled.');
                return;
            }

            var subscribedMarkets = new Set();

            function formatUsdtAmount(value) {
                var abs = Math.abs(value);
                var decimals = abs >= 1 ? 2 : (abs >= 0.01 ? 4 : 8);
                return abs.toLocaleString('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: decimals
                });
            }

            function renderDistanceCell(amount, pct) {
                var absAmount = formatUsdtAmount(amount);
                var absPct = Math.abs(pct).toFixed(2);
                if (pct > 0) {
                    return '<div class="d-inline-flex align-items-center gap-2 px-2 py-1 rounded"' +
                        ' style="background:rgba(255,159,67,.08);border:1px solid rgba(255,159,67,.28)">' +
                        '<span class="font-number fw-bold text-warning" style="font-size:.88rem;line-height:1.2;white-space:nowrap">' +
                        absAmount +
                        ' <small class="fw-normal text-muted" style="font-size:.7rem">USDT</small></span>' +
                        '<span class="badge rounded-pill bg-warning text-dark font-number" style="font-size:.68rem">' +
                        absPct + '٪</span>' +
                        '<span class="small fw-semibold text-warning" style="font-size:.72rem;white-space:nowrap">تا هدف</span></div>';
                }
                return '<div class="d-inline-flex align-items-center gap-2 px-2 py-1 rounded"' +
                    ' style="background:rgba(40,199,111,.08);border:1px solid rgba(40,199,111,.28)">' +
                    '<span class="font-number fw-bold text-success" style="font-size:.88rem;line-height:1.2;white-space:nowrap">' +
                    absAmount + ' <small class="fw-normal text-muted" style="font-size:.7rem">USDT</small></span>' +
                    '<span class="badge rounded-pill bg-success font-number" style="font-size:.68rem">' +
                    absPct + '٪</span>' +
                    '<span class="small fw-semibold text-success" style="font-size:.72rem;white-space:nowrap">بالاتر از هدف</span></div>';
            }

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
                        var prevPrice = parseFloat(badge.dataset.prevPrice);
                        if (!isFinite(prevPrice)) {
                            prevPrice = parseFloat(String(badge.textContent).replace(/,/g,
                                ''));
                        }

                        badge.textContent = price.toLocaleString('en-US', {
                            maximumFractionDigits: 8
                        });

                        badge.classList.remove('text-success', 'text-danger');
                        if (isFinite(prevPrice) && prevPrice > 0) {
                            if (price > prevPrice) {
                                badge.classList.add('text-success');
                            } else if (price < prevPrice) {
                                badge.classList.add('text-danger');
                            }
                        }
                        badge.dataset.prevPrice = price;
                    });

                    // Recalculate "distance to target" for OPEN rows of this symbol
                    document.querySelectorAll('.distance-cell[data-symbol="' + symbol +
                        '"][data-target]').forEach(function(cell) {
                        var targetPrice = parseFloat(cell.dataset.target);
                        if (!isFinite(targetPrice)) return;

                        var amount = targetPrice - price;
                        var pct = (amount / price) * 100;
                        cell.innerHTML = renderDistanceCell(amount, pct);
                    });
                });
            });
        });
    </script>
    @include('dashboard.bot.orders.partials.order-description-scripts')
@endsection
