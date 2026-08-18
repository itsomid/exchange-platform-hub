@extends('dashboard.layout.master')

@section('title', 'وضعیت ربات کاربر')

@section('content')

    <div class="row">
        <div class="col-12">

            <div class="card mb-4">
                {{-- User identity header + auto-trade toggle --}}
                <div class="card-header"
                    style="background: var(--bs-card-bg, #fff); border-bottom: 1px solid rgba(0,0,0,.08);">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                <h4 class="mb-0">وضعیت ربات کاربر</h4>
                                @if ($settings->auto_trade_enabled)
                                    <span class="badge bg-success px-3" id="bot-status-badge">ربات روشن</span>
                                @else
                                    <span class="badge bg-secondary px-3" id="bot-status-badge">ربات خاموش</span>
                                @endif
                            </div>
                            <div class="small text-muted d-flex flex-wrap align-items-center gap-2">
                                <span><i class="fas fa-user fa-xs me-1"></i>{{ $user->email }}</span>
                                <span class="opacity-50">|</span>
                                <span>{{ $user->mobile }}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center flex-wrap gap-3">
                            <div class="form-check form-switch m-0 d-flex align-items-center gap-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="autoTradeSwitch"
                                    data-url="{{ route('admin.bot.order.user.toggle', $user->id) }}"
                                    {{ $settings->auto_trade_enabled ? 'checked' : '' }}
                                    style="width:3rem;height:1.5rem;cursor:pointer;">
                                <label class="form-check-label small" for="autoTradeSwitch">خرید و فروش خودکار</label>
                            </div>
                            <button type="button" class="btn btn-danger btn-sm js-bot-cancel"
                                data-preview-url="{{ route('admin.bot.order.user.cancel-all-preview', $user) }}"
                                data-cancel-url="{{ route('admin.bot.order.user.cancel-all', $user) }}"
                                data-title="لغو همه سفارش‌ها و آزادسازی وجوه کاربر" data-mode="all">
                                <i class="fas fa-ban me-1"></i> لغو همه سفارش‌ها و آزادسازی وجوه
                            </button>
                            <a href="{{ route('admin.bot.order.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-right me-1"></i> بازگشت به لیست
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- ── Row 1: real-money state ─────────────────────────────── --}}
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-3">
                            <div class="border rounded p-3 text-center h-100" style="background:rgba(105,108,255,.06)">
                                <small class="text-muted d-block mb-1"><i class="fas fa-wallet fa-xs me-1"></i>مقدار واقعی
                                    سرمایه‌گذاری</small>
                                <div class="fw-bold fs-5 font-number">{{ formatNumberTrimZeros($actualInvestment) }}</div>
                                <small class="text-muted">USDT</small>
                                <small class="text-muted">مقدار واریزی + سود محقق شده</small>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="border border-success rounded p-3 text-center h-100"
                                style="background:rgba(40,199,111,.06)">
                                <small class="text-muted d-block mb-1"><i
                                        class="fas fa-cash-register fa-xs me-1 text-success"></i>قابل برداشت</small>
                                <div class="fw-bold fs-5 font-number text-success">
                                    {{ formatNumberTrimZeros($withdrawable) }}
                                </div>
                                <small class="text-muted">USDT</small>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div id="card-locked"
                                class="card-clickable border border-warning rounded p-3 text-center h-100 d-flex flex-column justify-content-between"
                                style="background:rgba(255,159,67,.06); cursor:pointer;">
                                <small class="text-muted d-block mb-1"><i
                                        class="fas fa-lock fa-xs me-1 text-warning"></i>قفل‌شده (در انتظار فروش)</small>
                                <div class="fw-bold fs-5 font-number text-warning">{{ formatNumberTrimZeros($locked) }}
                                </div>
                                <small class="text-muted">USDT</small>
                                <div class="mt-2">
                                    <span class="badge bg-warning bg-opacity-10 text-warning" style="font-size:.7rem;">
                                        <i class="fas fa-chevron-down fa-xs me-1"></i>جزئیات
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <div id="card-pnl"
                                class="card-clickable border rounded p-3 text-center h-100 d-flex flex-column justify-content-between
                                {{ $totalPnl > 0 ? 'border-success' : ($totalPnl < 0 ? 'border-danger' : '') }}"
                                style="background:{{ $totalPnl > 0 ? 'rgba(40,199,111,.06)' : ($totalPnl < 0 ? 'rgba(234,84,85,.06)' : '') }}; cursor:pointer;">
                                <small class="text-muted d-block mb-1"><i class="fas fa-chart-line fa-xs me-1"></i>سود /
                                    زیان
                                    خالص (P&L)</small>
                                <div dir="ltr"
                                    class="fw-bold fs-5 font-number {{ $totalPnl >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $totalPnl >= 0 ? '+' : '' }}{{ formatNumberTrimZeros($totalPnl) }}
                                </div>
                                <small class="text-muted">USDT</small>
                                <div class="mt-1 d-flex justify-content-center gap-2">
                                    <span class="badge bg-success bg-opacity-25 text-success font-number"
                                        style="font-size:.72rem">▲ {{ formatNumberTrimZeros($positivePnl) }}</span>
                                    <span class="badge bg-danger bg-opacity-25 text-danger font-number"
                                        style="font-size:.72rem">▼ {{ formatNumberTrimZeros($negativePnl) }}</span>
                                </div>
                                <div class="mt-2">
                                    <span
                                        class="badge {{ $totalPnl >= 0 ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}"
                                        style="font-size:.7rem;">
                                        <i class="fas fa-chevron-down fa-xs me-1"></i>جزئیات
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── Row 2: flows + lifetime aggregates ──────────────────── --}}
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="border rounded p-3 text-center h-100">
                                <small class="text-muted d-block mb-1"><i
                                        class="fas fa-arrow-down fa-xs me-1 text-success"></i>واریزی واقعی به بات از کیف پول
                                    اصلی</small>
                                <div class="fw-bold fs-6 font-number">{{ formatNumberTrimZeros($deposits) }}</div>
                                <small class="text-muted">USDT</small>
                                <small class="text-muted d-block mt-1 font-number">
                                    {{ formatNumberTrimZeros($deposits - $depositTransferFee) }}
                                    + ({{ formatNumberTrimZeros($depositTransferFee) }} کارمزد)
                                </small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border rounded p-3 text-center h-100">
                                <small class="text-muted d-block mb-1"><i
                                        class="fas fa-arrow-up fa-xs me-1 text-danger"></i>برداشت کلی از بات</small>
                                <div class="fw-bold fs-6 font-number">{{ formatNumberTrimZeros($withdrawals) }}</div>
                                <small class="text-muted">USDT</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border rounded p-3 text-center h-100">
                                <small class="text-muted d-block mb-1"><i class="fas fa-coins fa-xs me-1"></i>مجموع تخصیص به
                                    بات (ناخالص)</small>
                                <div class="fw-bold fs-6 font-number">{{ formatNumberTrimZeros($grossAllocated) }}</div>
                                <small class="text-muted">USDT</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border rounded p-3 text-center h-100">
                                <small class="text-muted d-block mb-1"><i
                                        class="fas fa-unlock fa-xs me-1 text-success"></i>آزادشده (برگشتی) کل</small>
                                <div class="fw-bold fs-6 font-number">{{ formatNumberTrimZeros($freedUsdt) }}</div>
                                <small class="text-muted">USDT</small>
                            </div>
                        </div>
                        {{-- Platform revenue card — click to expand breakdown --}}
                        <div class="col-6 col-md-3">
                            <div id="card-platform-revenue"
                                class="card-clickable border border-info rounded p-3 text-center h-100 d-flex flex-column justify-content-between"
                                style="background:rgba(3,195,236,.05); cursor:pointer;">
                                <small class="text-muted d-block mb-1">
                                    <i class="fas fa-building fa-xs me-1 text-info"></i>درآمد کلی صرافی( واریز + کارمزد
                                    عملکرد)
                                </small>
                                <div class="fw-bold fs-6 font-number text-info">
                                    {{ formatNumberTrimZeros($platformRevenue) }}
                                </div>
                                <small class="text-muted">USDT</small>
                                <div class="mt-2">
                                    <span class="badge bg-info bg-opacity-10 text-info" style="font-size:.7rem;">
                                        <i class="fas fa-chevron-down fa-xs me-1"></i>جزئیات
                                    </span>
                                </div>
                            </div>
                        </div>
                        {{-- Total fees card — click to expand breakdown --}}
                        <div class="col-6 col-md-3">
                            <div id="card-total-fees"
                                class="card-clickable border rounded p-3 text-center h-100 d-flex flex-column justify-content-between"
                                style="background:rgba(234,84,85,.05); cursor:pointer;">
                                <small class="text-muted d-block mb-1">
                                    <i class="fas fa-receipt fa-xs me-1 text-danger"></i>کارمزد پرداختی (کل)
                                </small>
                                <div class="fw-bold fs-6 font-number text-danger">
                                    {{ formatNumberTrimZeros($totalFees) }}
                                </div>
                                <small class="text-muted">USDT</small>
                                <div class="mt-2">
                                    <span class="badge bg-danger bg-opacity-10 text-danger" style="font-size:.7rem;">
                                        <i class="fas fa-chevron-down fa-xs me-1"></i>جزئیات
                                    </span>
                                </div>
                            </div>
                        </div>
                        @if ($introducer)
                            <div class="col-6 col-md-3">
                                <div id="card-referral"
                                    class="card-clickable border border-info rounded p-3 text-center h-100 d-flex flex-column justify-content-between"
                                    style="background:rgba(3,195,236,.05); cursor:pointer;">
                                    <small class="text-muted d-block mb-1">
                                        <i class="fas fa-user-friends fa-xs me-1 text-info"></i>مجموع پرداختی به معرف (کل
                                        سفارش‌ها)
                                    </small>
                                    <div class="fw-bold fs-6 font-number text-info">
                                        {{ formatNumberTrimZeros($referralPaid) }}
                                    </div>
                                    <small class="text-muted">USDT</small>
                                    <div class="mt-2">
                                        <span class="badge bg-info bg-opacity-10 text-info" style="font-size:.7rem;">
                                            <i class="fas fa-chevron-down fa-xs me-1"></i>جزئیات
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>



                    {{-- Hidden popover contents (not rendered in DOM flow) --}}
                    <div class="d-none">
                        <div id="pop-locked">
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">
                                <small class="text-muted">قفل بابت پله‌های فروش باز</small>
                                <span class="font-number">{{ formatNumberTrimZeros($lockedOpenCost) }} <small
                                        style="font-size:.5rem;" class="text-muted">USDT</small></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">
                                <small class="text-muted">باقیمانده کارمزد خرید (پرداخت‌شده با کوین)</small>
                                <span class="font-number text-warning">{{ formatNumberTrimZeros($lockedResidual) }}
                                    <small style="font-size:.5rem;" class="text-muted">USDT</small></span>
                            </div>
                            @foreach ($baseFeeBuys as $bf)
                                <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">
                                    <small class="text-muted">کارمزد خرید {{ $bf->fee_currency }} (با خود کوین)</small>
                                    <span class="font-number">{{ formatNumberTrimZeros($bf->fee_usdt) }} <small
                                            style="font-size:.5rem;" class="text-muted">USDT</small></span>
                                </div>
                            @endforeach
                            <div class="small text-muted pt-2" style="font-size:.7rem; line-height:1.8;">
                                هنگام خرید، کل مبلغ تخصیص‌یافته (شامل کارمزد) در «قفل‌شده» می‌رود؛ اما وقتی صرافی مرجع
                                کارمزد خرید را با خود کوین برمی‌دارد، مقدار خریداری‌شده ثبت‌شده خالصِ کارمزد است و
                                تسویه‌ها فقط «مقدار × قیمت خرید» را آزاد می‌کنند. بنابراین معادل همان کارمزد در
                                قفل‌شده باقی می‌ماند. این کارمزد قبلاً در سود/زیان خالص کاربر لحاظ شده است.
                            </div>
                        </div>
                        <div id="pop-pnl">
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">
                                <small class="text-muted">درآمد کل فروش (gross_revenue)</small>
                                <span class="font-number">{{ formatNumberTrimZeros($grossRevenue) }} <small
                                        style="font-size:.5rem;" class="text-muted">USDT</small></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">
                                <small class="text-muted">بهای تمام‌شده (cost_basis)</small>
                                <span class="font-number">{{ formatNumberTrimZeros($freedUsdt) }} <small
                                        style="font-size:.5rem;" class="text-muted">USDT</small></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">
                                <small class="text-muted">سود/زیان قیمتی (فروش − خرید)</small>
                                <span dir="ltr"
                                    class="font-number {{ $pricePnl >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $pricePnl >= 0 ? '+' : '' }}{{ formatNumberTrimZeros($pricePnl) }} <small
                                        style="font-size:.5rem;" class="text-muted">USDT</small></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">
                                <small class="text-muted">− کارمزد شبکه</small>
                                <span class="font-number text-warning">{{ formatNumberTrimZeros($networkFee) }} <small
                                        style="font-size:.5rem;" class="text-muted">USDT</small></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">
                                <small class="text-muted">− کارمزد صرافی مرجع (خرید+فروش، در تسویه)</small>
                                <span class="font-number text-warning">{{ formatNumberTrimZeros($settledExchangeFee) }}
                                    <small style="font-size:.5rem;" class="text-muted">USDT</small></span>
                            </div>
                            @if ($performanceFee > 0)
                                <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">
                                    <small class="text-muted">− کارمزد عملکرد</small>
                                    <span class="font-number text-warning">{{ formatNumberTrimZeros($performanceFee) }}
                                        <small style="font-size:.5rem;" class="text-muted">USDT</small></span>
                                </div>
                            @endif
                            @if ($cancelFee > 0)
                                <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">
                                    <small class="text-muted">− کارمزد لغو</small>
                                    <span class="font-number text-warning">{{ formatNumberTrimZeros($cancelFee) }}
                                        <small style="font-size:.5rem;" class="text-muted">USDT</small></span>
                                </div>
                            @endif
                            <div class="d-flex justify-content-between align-items-center py-1 gap-3">
                                <small class="fw-bold">سود/زیان خالص (P&L)</small>
                                <span dir="ltr"
                                    class="font-number fw-bold {{ $totalPnl >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $totalPnl >= 0 ? '+' : '' }}{{ formatNumberTrimZeros($totalPnl) }} <small
                                        style="font-size:.5rem;" class="text-muted">USDT</small></span>
                            </div>
                            <div class="small text-muted pt-2 border-top mt-1" style="font-size:.7rem; line-height:1.8;">
                                جمع ردیف‌های net_pnl تسویه‌هاست: (درآمد فروش − بهای خرید) منهای کارمزدهای شبکه،
                                صرافی مرجع، عملکرد و لغو. کارمزد واریز/برداشت کیف ربات در این عدد نیست و در
                                کارت «کارمزد پرداختی (کل)» دیده می‌شود.
                            </div>
                        </div>
                        <div id="pop-platform-revenue">
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">
                                <small class="text-muted">کارمزد عملکرد</small>
                                <span class="font-number">{{ formatNumberTrimZeros($performanceFee) }} <small
                                        style="font-size: .5rem;" class="text-muted">USDT</small>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-2">
                                <small class="text-muted">کارمزد واریز به ربات</small>
                                <span class="font-number">{{ formatNumberTrimZeros($depositTransferFee) }} <small
                                        style="font-size: .5rem;" class="text-muted">USDT</small></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-1 gap-2">
                                <small class="text-muted">کارمزد برداشت از ربات</small>
                                <span class="font-number">{{ formatNumberTrimZeros($withdrawTransferFee) }}
                                    <small style="font-size: .5rem;" class="text-muted">USDT</small></span>
                            </div>
                        </div>
                        <div id="pop-total-fees">
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-2">
                                <small class="text-muted">کارمزد واریز به ربات</small>
                                <span class="font-number">{{ formatNumberTrimZeros($depositTransferFee) }} <small
                                        style="font-size: .5rem;" class="text-muted">USDT</small></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-2">
                                <small class="text-muted">کارمزد برداشت از ربات</small>
                                <span class="font-number">{{ formatNumberTrimZeros($withdrawTransferFee) }}
                                    <small style="font-size: .5rem;" class="text-muted">USDT</small></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">
                                <small class="text-muted">کارمزد صرافی مرجع (خرید+فروش)</small>
                                <span class="font-number">{{ formatNumberTrimZeros($refExchangeFee) }} <small
                                        style="font-size: .5rem;" class="text-muted">USDT</small></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">
                                <small class="text-muted">کارمزد شبکه</small>
                                <span class="font-number">{{ formatNumberTrimZeros($networkFee) }} <small
                                        style="font-size: .5rem;" class="text-muted">USDT</small></span>
                            </div>
                            @if ($performanceFee > 0)
                                <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">
                                    <small class="text-muted">کارمزد عملکرد</small>
                                    <span class="font-number">{{ formatNumberTrimZeros($performanceFee) }} <small
                                            style="font-size: .5rem;" class="text-muted">USDT</small></span>
                                </div>
                            @endif
                            @if ($cancelFee > 0)
                                <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">
                                    <small class="text-muted">کارمزد لغو</small>
                                    <span class="font-number">{{ formatNumberTrimZeros($cancelFee) }} <small
                                            class="text-muted">USDT</small></span>
                                </div>
                            @endif
                        </div>
                        @if ($introducer)
                            <div id="pop-referral">
                                <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">
                                    <small class="text-muted">شناسه</small>
                                    <span class="font-number">#{{ $introducer->id }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">
                                    <small class="text-muted">نام</small>
                                    <span>{{ trim($introducer->fullname()) ?: '—' }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">
                                    <small class="text-muted">نام کاربری</small>
                                    <span>{{ $introducer->username ?: '—' }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">
                                    <small class="text-muted">ایمیل</small>
                                    <span>{{ $introducer->email ?: '—' }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">
                                    <small class="text-muted">موبایل</small>
                                    <span class="font-number">{{ $introducer->mobile ?: '—' }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-1 border-bottom gap-3">
                                    <small class="text-muted">کد معرف</small>
                                    <span class="font-monospace">{{ $user->introducerReferral?->code ?: '—' }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-1 gap-3">
                                    <small class="text-muted">پرداختی کل</small>
                                    <span class="font-number text-info">{{ formatNumberTrimZeros($referralPaid) }}
                                        <small style="font-size:.5rem;" class="text-muted">USDT</small></span>
                                </div>
                                <div class="pt-2 mt-1 border-top text-center">
                                    <a href="{{ route('admin.user.edit', $introducer) }}" class="small text-info"
                                        onclick="event.stopPropagation()">
                                        <i class="fas fa-external-link-alt fa-xs me-1"></i>مشاهده پروفایل معرف
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Capital allocation chart --}}
                    <div class="row align-items-center g-3">
                        <div class="col-md-3">
                            <div id="capital-distribution-chart" style="min-height:150px;">
                                <div class="d-flex justify-content-center align-items-center" style="height:150px;">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <small class="text-muted d-block mb-2">وضعیت تخصیص سرمایه واقعی</small>
                            <ul class="list-unstyled mb-0 small">
                                <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                    <span><i class="fas fa-circle text-warning me-2" style="font-size:.55rem"></i>قفل‌شده
                                        (در انتظار فروش)</span>
                                    <span class="font-number">{{ formatNumberTrimZeros($locked) }} <small
                                            class="text-muted">USDT</small></span>
                                </li>
                                <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                    <span><i class="fas fa-circle text-success me-2" style="font-size:.55rem"></i>قابل
                                        برداشت</span>
                                    <span class="font-number">{{ formatNumberTrimZeros($withdrawable) }} <small
                                            class="text-muted">USDT</small></span>
                                </li>
                                <li class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                    <span class="fw-semibold">مجموع (سرمایه واقعی)</span>
                                    <span class="font-number">{{ formatNumberTrimZeros($actualInvestment) }} <small
                                            class="text-muted">USDT</small></span>
                                </li>
                                <li class="d-flex justify-content-between align-items-center py-1">
                                    <span class="text-muted"><i class="fas fa-circle text-info me-2"
                                            style="font-size:.55rem"></i>سود محقق‌شده (تجمیعی)</span>
                                    <span class="font-number">{{ formatNumberTrimZeros($realizedProfit) }} <small
                                            class="text-muted">USDT</small></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- User's orders --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">سفارش‌های کاربر</h5>
                    <span class="badge bg-secondary">{{ $orders->total() }} سفارش</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>UUID</th>
                                    <th>مبلغ کل (USDT)</th>
                                    <th>وضعیت</th>
                                    <th>تریگر</th>
                                    <th>یادداشت ادمین</th>
                                    <th>تاریخ ایجاد</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($orders as $order)
                                    @php
                                        $badgeClass = match ($order->status) {
                                            'FILLED' => 'bg-success',
                                            'PENDING' => 'bg-warning text-dark',
                                            'PARTIALLY_FILLED' => 'bg-info',
                                            'FAILED' => 'bg-danger',
                                            'CANCELED' => 'bg-danger',
                                            default => 'bg-secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $order->id }}</td>
                                        <td><code class="small">{{ Str::limit($order->batch_uuid, 20) }}</code></td>
                                        <td class="font-number">{{ number_format($order->total_amount_usdt, 2) }}</td>
                                        <td><span class="badge {{ $badgeClass }}">{{ $order->status }}</span></td>
                                        <td><small>{{ $order->triggered_by }}</small></td>
                                        <td style="max-width:180px;">
                                            <div id="order-admin-description-preview-{{ $order->id }}"
                                                class="small text-muted text-truncate"
                                                title="{{ $order->admin_description ?? '' }}">
                                                {{ $order->admin_description ? Str::limit($order->admin_description, 60) : '—' }}
                                            </div>
                                        </td>
                                        <td><small>{{ $order->created_at?->format('Y-m-d H:i') }}</small></td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                @if ($order->description)
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger js-view-order-system-description"
                                                        title="توضیحات سیستمی" data-order-id="{{ $order->id }}">
                                                        <i class="fas fa-robot"></i>
                                                    </button>
                                                @endif
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-secondary js-edit-order-admin-description"
                                                    title="یادداشت ادمین" data-order-id="{{ $order->id }}"
                                                    data-update-url="{{ route('admin.bot.order.update-description', $order) }}"
                                                    data-admin-description="{{ e($order->admin_description ?? '') }}">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <a href="{{ route('admin.bot.order.show', $order) }}"
                                                    class="btn btn-sm btn-outline-primary" title="جزئیات">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                            @if ($order->description)
                                                <div id="system-description-html-{{ $order->id }}" class="d-none">
                                                    @include(
                                                        'dashboard.bot.orders.partials.system-description-segments',
                                                        [
                                                            'description' => $order->description,
                                                        ]
                                                    )
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">هیچ سفارشی یافت نشد.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $orders->links() }}
                </div>
            </div>

        </div>
    </div>

    @include('dashboard.bot.orders.partials.description-modal')
    @include('dashboard.bot.orders.partials.system-description-modal')
    @include('dashboard.bot.orders.partials.cancel-orders-modal')

@endsection

@section('vendor-style')
    <style>
        .card-clickable {
            transition: box-shadow .15s ease, transform .1s ease;
            user-select: none;
        }

        .card-clickable:hover {
            box-shadow: 0 4px 14px rgba(0, 0, 0, .12);
            transform: translateY(-1px);
        }

        .card-clickable:active {
            transform: translateY(0);
            box-shadow: none;
        }

        .popover-fee-detail {
            min-width: 350px;
            font-size: .82rem;
            direction: rtl;
            text-align: right;
        }

        .popover-fee-detail .popover-header {
            font-weight: 700;
            font-size: .82rem;
        }

        .popover-fee-detail .popover-body {
            padding: .6rem .75rem;
        }
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ── Click-to-reveal popovers ─────────────────────────────────────
            [{
                    cardId: 'card-locked',
                    contentId: 'pop-locked',
                    title: 'تفکیک موجودی قفل‌شده'
                },
                {
                    cardId: 'card-pnl',
                    contentId: 'pop-pnl',
                    title: 'نحوه محاسبه سود/زیان خالص'
                },
                {
                    cardId: 'card-platform-revenue',
                    contentId: 'pop-platform-revenue',
                    title: 'تفکیک درآمد صرافی'
                },
                {
                    cardId: 'card-total-fees',
                    contentId: 'pop-total-fees',
                    title: 'تفکیک کارمزدهای پرداختی'
                },
                {
                    cardId: 'card-referral',
                    contentId: 'pop-referral',
                    title: 'اطلاعات معرف'
                },
            ].forEach(function(cfg) {
                var card = document.getElementById(cfg.cardId);
                var contentEl = document.getElementById(cfg.contentId);
                if (!card || !contentEl || typeof bootstrap === 'undefined') return;

                var pop = new bootstrap.Popover(card, {
                    html: true,
                    sanitize: false,
                    trigger: 'click',
                    placement: 'bottom',
                    title: cfg.title,
                    content: contentEl.innerHTML,
                    customClass: 'popover-fee-detail',
                });

                // Close when clicking anywhere outside the card or the popover itself.
                document.addEventListener('click', function(e) {
                    if (!card.contains(e.target)) {
                        pop.hide();
                    }
                });
            });

            // ── Capital distribution donut chart ─────────────────────────────
            (function() {
                var chartEl = document.getElementById('capital-distribution-chart');
                if (!chartEl) return;

                if (typeof ApexCharts === 'undefined') {
                    chartEl.innerHTML =
                        '<div class="text-center text-muted py-4"><i class="fa fa-chart-pie me-1"></i>نمودار در دسترس نیست</div>';
                    return;
                }

                var locked = {{ (float) $locked }};
                var withdrawable = {{ (float) $withdrawable }};

                if (locked + withdrawable <= 0) {
                    chartEl.innerHTML =
                        '<div class="text-center text-muted py-4"><i class="fa fa-info-circle me-1"></i>داده‌ای برای نمایش وجود ندارد</div>';
                    return;
                }

                new ApexCharts(chartEl, {
                    series: [locked, withdrawable],
                    chart: {
                        type: 'donut',
                        height: 150,
                        fontFamily: 'inherit',
                        sparkline: {
                            enabled: true
                        },
                    },
                    labels: ['قفل‌شده', 'قابل برداشت'],
                    colors: ['#ff9f43', '#28c76f'],
                    stroke: {
                        width: 2,
                    },
                    dataLabels: {
                        enabled: false,
                    },
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return val.toLocaleString('en-US', {
                                    maximumFractionDigits: 8
                                }) + ' USDT';
                            }
                        }
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '78%',
                                labels: {
                                    show: true,
                                    name: {
                                        show: false
                                    },
                                    value: {
                                        show: false
                                    },
                                    total: {
                                        show: true,
                                        showAlways: true,
                                        label: 'قفل‌شده',
                                        fontSize: '.7rem',
                                        fontWeight: 700,
                                        formatter: function(w) {
                                            var total = w.globals.seriesTotals.reduce(function(a,
                                                b) {
                                                return a + b;
                                            }, 0);
                                            var lockedShare = total > 0 ? (w.globals.series[0] /
                                                    total) *
                                                100 : 0;
                                            return lockedShare.toFixed(0) + '%';
                                        }
                                    }
                                }
                            }
                        }
                    },
                }).render();
            })();

            var toast = function(text, ok) {
                Toastify({
                    text: text,
                    duration: ok ? 3000 : 5000,
                    close: true,
                    gravity: 'top',
                    position: 'right',
                    style: {
                        background: ok ? '#28C76F' : '#EA5455'
                    },
                }).showToast();
            };

            var sw = document.getElementById('autoTradeSwitch');
            if (!sw) return;

            sw.addEventListener('change', function() {
                sw.disabled = true;
                fetch(sw.dataset.url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                    })
                    .then(function(res) {
                        if (!res.ok) throw new Error();
                        return res.json();
                    })
                    .then(function(data) {
                        sw.checked = data.enabled;
                        var badge = document.getElementById('bot-status-badge');
                        if (badge) {
                            badge.textContent = data.enabled ? 'ربات روشن' : 'ربات خاموش';
                            badge.className = 'badge px-3 ' + (data.enabled ? 'bg-success' :
                                'bg-secondary');
                        }
                        toast(data.message, true);
                    })
                    .catch(function() {
                        sw.checked = !sw.checked;
                        toast('خطا در تغییر وضعیت ربات. دوباره تلاش کنید.', false);
                    })
                    .finally(function() {
                        sw.disabled = false;
                    });
            });
        });
    </script>
    @include('dashboard.bot.orders.partials.order-description-scripts')
@endsection
