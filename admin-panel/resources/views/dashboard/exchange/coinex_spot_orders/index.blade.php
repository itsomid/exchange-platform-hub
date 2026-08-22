@extends('dashboard.layout.master')

@section('title', 'سفارش‌های اسپات CoinEx')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
    <style>
        .coinex-orders-page .stat-card {
            border: 0;
            border-radius: 1rem;
            overflow: hidden;
            position: relative;
        }

        .coinex-orders-page .stat-card::before {
            content: '';
            position: absolute;
            inset: 0;
            opacity: 0.12;
            background: radial-gradient(circle at top left, #fff 0%, transparent 55%);
            pointer-events: none;
        }

        .coinex-orders-page .stat-buy {
            background: linear-gradient(135deg, #28c76f 0%, #1f9d57 100%);
            color: #fff;
        }

        .coinex-orders-page .stat-sell {
            background: linear-gradient(135deg, #ea5455 0%, #c73e3f 100%);
            color: #fff;
        }

        .coinex-orders-page .stat-pending {
            background: linear-gradient(135deg, #ff9f43 0%, #e07b1f 100%);
            color: #fff;
        }

        .coinex-orders-page .stat-finished {
            background: linear-gradient(135deg, #7367f0 0%, #5a52d6 100%);
            color: #fff;
        }

        .coinex-orders-page .stat-balance-base {
            background: linear-gradient(135deg, #00cfe8 0%, #1a9bb0 100%);
            color: #fff;
        }

        .coinex-orders-page .stat-balance-usdt {
            background: linear-gradient(135deg, #28c76f 0%, #198754 100%);
            color: #fff;
        }

        .coinex-orders-page .balance-meta {
            font-size: 0.78rem;
            opacity: 0.9;
        }

        .coinex-orders-page .market-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.85rem;
            border-radius: 999px;
            background: rgba(115, 103, 240, 0.1);
            color: #7367f0;
            font-weight: 700;
        }

        .coinex-orders-page .side-badge-buy {
            background: rgba(40, 199, 111, 0.12);
            color: #28c76f;
        }

        .coinex-orders-page .side-badge-sell {
            background: rgba(234, 84, 85, 0.12);
            color: #ea5455;
        }

        .coinex-orders-page .order-section-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .coinex-orders-page .order-section-title .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .coinex-orders-page .order-section-title .dot-buy {
            background: #28c76f;
        }

        .coinex-orders-page .order-section-title .dot-sell {
            background: #ea5455;
        }

        .coinex-orders-page .table thead th {
            white-space: nowrap;
            font-size: 0.78rem;
            text-transform: none;
            letter-spacing: 0;
        }

        .coinex-orders-page .table td {
            vertical-align: middle;
            font-variant-numeric: tabular-nums;
        }

        .coinex-orders-page .empty-state {
            padding: 2.5rem 1rem;
            text-align: center;
            color: #6c757d;
        }

        .coinex-orders-page .nav-pills .nav-link {
            border-radius: 999px;
            font-weight: 600;
            color: #6c757d;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
        }

        .coinex-orders-page .nav-pills .nav-link.active {
            color: #fff;
            border-color: transparent;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.28);
        }

        .coinex-orders-page .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 0.75rem;
        }

        .coinex-orders-page .detail-item {
            background: #f8f9fa;
            border-radius: 0.75rem;
            padding: 0.75rem 0.9rem;
        }

        .coinex-orders-page .detail-item .label {
            display: block;
            font-size: 0.72rem;
            color: #6c757d;
            margin-bottom: 0.2rem;
        }

        .coinex-orders-page .detail-item .value {
            font-weight: 600;
            word-break: break-all;
        }

        .coinex-orders-page .lookup-panel {
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px dashed #d9dee3;
        }

        .coinex-orders-page .lookup-panel .lookup-title {
            font-weight: 700;
            margin-bottom: 0.15rem;
        }

        .coinex-order-lookup-modal .modal-content {
            border: 0;
            border-radius: 1rem;
            overflow: hidden;
        }

        .coinex-order-lookup-modal .modal-header {
            border-bottom: 0;
            color: #fff;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .coinex-order-lookup-modal .modal-header.is-buy {
            background: linear-gradient(135deg, #28c76f 0%, #1f9d57 100%);
        }

        .coinex-order-lookup-modal .modal-header.is-sell {
            background: linear-gradient(135deg, #ea5455 0%, #c73e3f 100%);
        }

        .coinex-order-lookup-modal .modal-header .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        .coinex-order-lookup-modal .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.2rem 0.7rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
            font-size: 0.78rem;
            font-weight: 700;
        }

        .coinex-order-lookup-modal pre.raw-json {
            background: #1e1e2d;
            color: #e4e6f1;
            border-radius: 0.75rem;
            padding: 1rem;
            font-size: 0.75rem;
            max-height: 280px;
            overflow: auto;
            direction: ltr;
            text-align: left;
            margin: 0;
        }
    </style>
@endsection

@section('content')
    @php
        $fmt = fn($v) => formatNumberTrimZeros($v ?? 0);
        $fmtTs = function ($ts) {
            if (!$ts) {
                return '—';
            }
            $ms = is_numeric($ts) ? (int) $ts : 0;
            if ($ms <= 0) {
                return '—';
            }
            return \Carbon\Carbon::createFromTimestampMs($ms)->timezone(config('app.timezone'))->format('Y/m/d H:i:s');
        };
        $pendingBuyCount = count($pendingBuy['data'] ?? []);
        $pendingSellCount = count($pendingSell['data'] ?? []);
        $finishedBuyCount = count($finishedBuy['data'] ?? []);
        $finishedSellCount = count($finishedSell['data'] ?? []);
        $pendingTotal = (int) ($pendingBuy['pagination']['api_total'] ?? ($pendingBuyCount + $pendingSellCount));
        $finishedTotal = (int) ($finishedBuy['pagination']['api_total'] ?? ($finishedBuyCount + $finishedSellCount));
    @endphp

    <div class="coinex-orders-page">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <h5 class="mb-1">سفارش‌های اسپات صرافی مرجع (CoinEx)</h5>
                        <p class="text-muted mb-0 small">
                            سفارش‌های باز و تکمیل‌شده خرید/فروش اسپات را بر اساس کوین از API نسخه ۲ کوینکس دریافت کنید.
                        </p>
                    </div>
                    @if ($market)
                        <span class="market-pill">
                            <i class="fa-light fa-chart-candlestick"></i>
                            {{ $market }}
                        </span>
                    @endif
                </div>

                <form action="{{ route('admin.ref-exchange.coinex-spot-orders.index') }}" method="get" class="row g-3 align-items-end">
                    <div class="col-lg-5 col-md-6">
                        <label class="form-label" for="currency_id">انتخاب کوین</label>
                        <x-currency-select
                            name="currency_id"
                            :currencies="$currencies"
                            :selected="old('currency_id', $selectedCurrency?->id ?? '')"
                            :required="true"
                            placeholder="کوین مورد نظر را انتخاب کنید..."
                        />
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <label class="form-label" for="limit">تعداد در صفحه</label>
                        <select name="limit" id="limit" class="form-select">
                            @foreach ([20, 50, 100] as $opt)
                                <option value="{{ $opt }}" @selected((int) $limit === $opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-5 col-md-12 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>
                            دریافت سفارش‌ها
                        </button>
                        <a href="{{ route('admin.ref-exchange.coinex-spot-orders.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>
                            پاک کردن
                        </a>
                    </div>
                </form>

                <div class="lookup-panel">
                    <div class="mb-2">
                        <div class="lookup-title">جستجو بر اساس شناسه سفارش CoinEx</div>
                        <p class="text-muted small mb-0">
                            شناسه سفارش CoinEx را وارد کنید. اگر کوین را هم انتخاب کنید جستجو سریع‌تر انجام می‌شود؛ در غیر این صورت در همه بازارهای فعال جستجو می‌شود.
                        </p>
                    </div>
                    <div id="coinex-order-lookup-form" class="row g-3 align-items-end">
                        <div class="col-lg-8 col-md-8">
                            <label class="form-label" for="lookup_order_id">شناسه سفارش</label>
                            <input type="text" inputmode="numeric" pattern="[0-9]*" autocomplete="off"
                                class="form-control" id="lookup_order_id" name="lookup_order_id"
                                value="{{ request('order_id') }}"
                                placeholder="مثال: 173390586784" required>
                        </div>
                        <div class="col-lg-4 col-md-4">
                            <button type="button" class="btn btn-primary w-100" id="coinex-order-lookup-btn">
                                <i class="fas fa-search me-1"></i>
                                جستجوی سفارش
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if ($error)
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <div>{{ $error }}</div>
            </div>
        @endif

        @if (!$selectedCurrency)
            <div class="card">
                <div class="empty-state">
                    <i class="fa-light fa-coins fa-3x mb-3 text-primary"></i>
                    <h5 class="mb-1">کوینی انتخاب نشده</h5>
                    <p class="mb-0">برای مشاهده سفارش‌های خرید و فروش اسپات CoinEx، یک کوین انتخاب کنید.</p>
                </div>
            </div>
        @elseif (!$error)
            @if ($balances)
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card stat-card stat-balance-base h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <small class="opacity-75">موجودی {{ $balances['base']['ccy'] }} (Base)</small>
                                    @if ($selectedCurrency?->coinLogo())
                                        <img src="{{ $selectedCurrency->coinLogo() }}" alt="{{ $balances['base']['ccy'] }}"
                                            style="width:28px;height:28px;border-radius:50%;object-fit:contain;background:#fff;">
                                    @endif
                                </div>
                                <h3 class="mb-2 text-white">{{ $fmt($balances['base']['total']) }}</h3>
                                <div class="balance-meta d-flex flex-wrap gap-3">
                                    <span>آزاد: {{ $fmt($balances['base']['available']) }}</span>
                                    <span>قفل: {{ $fmt($balances['base']['frozen']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card stat-card stat-balance-usdt h-100">
                            <div class="card-body">
                                <small class="d-block opacity-75 mb-2">موجودی USDT (Quote)</small>
                                <h3 class="mb-2 text-white">{{ $fmt($balances['usdt']['total']) }}</h3>
                                <div class="balance-meta d-flex flex-wrap gap-3">
                                    <span>آزاد: {{ $fmt($balances['usdt']['available']) }}</span>
                                    <span>قفل: {{ $fmt($balances['usdt']['frozen']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card stat-pending h-100">
                        <div class="card-body">
                            <small class="d-block opacity-75 mb-1">سفارش‌های باز</small>
                            <h3 class="mb-0 text-white">{{ number_format($pendingTotal) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card stat-finished h-100">
                        <div class="card-body">
                            <small class="d-block opacity-75 mb-1">سفارش‌های تکمیل‌شده</small>
                            <h3 class="mb-0 text-white">{{ number_format($finishedTotal) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card stat-buy h-100">
                        <div class="card-body">
                            <small class="d-block opacity-75 mb-1">خرید باز / تکمیل‌شده</small>
                            <h3 class="mb-0 text-white">{{ $pendingBuyCount }} / {{ $finishedBuyCount }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card stat-sell h-100">
                        <div class="card-body">
                            <small class="d-block opacity-75 mb-1">فروش باز / تکمیل‌شده</small>
                            <h3 class="mb-0 text-white">{{ $pendingSellCount }} / {{ $finishedSellCount }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body p-3">
                    <ul class="nav nav-pills nav-fill gap-2" id="coinexOrderTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active w-100 d-flex align-items-center justify-content-center gap-2 py-3"
                                id="pending-tab" data-bs-toggle="pill" data-bs-target="#pending-pane" type="button" role="tab">
                                <i class="fa-light fa-clock"></i>
                                سفارش‌های باز (Pending)
                                <span class="badge bg-white text-warning ms-1">{{ $pendingBuyCount + $pendingSellCount }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link w-100 d-flex align-items-center justify-content-center gap-2 py-3"
                                id="finished-tab" data-bs-toggle="pill" data-bs-target="#finished-pane" type="button" role="tab">
                                <i class="fa-light fa-circle-check"></i>
                                سفارش‌های تکمیل‌شده (Finished)
                                <span class="badge bg-white text-primary ms-1">{{ $finishedBuyCount + $finishedSellCount }}</span>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="pending-pane" role="tabpanel">
                    @include('dashboard.exchange.coinex_spot_orders._orders_section', [
                        'title' => 'سفارش‌های باز خرید',
                        'side' => 'buy',
                        'orders' => $pendingBuy['data'] ?? [],
                        'pagination' => $pendingBuy['pagination'] ?? [],
                        'cancellable' => true,
                        'market' => $market,
                        'fmt' => $fmt,
                        'fmtTs' => $fmtTs,
                    ])

                    @include('dashboard.exchange.coinex_spot_orders._orders_section', [
                        'title' => 'سفارش‌های باز فروش',
                        'side' => 'sell',
                        'orders' => $pendingSell['data'] ?? [],
                        'pagination' => $pendingSell['pagination'] ?? [],
                        'cancellable' => true,
                        'market' => $market,
                        'fmt' => $fmt,
                        'fmtTs' => $fmtTs,
                    ])
                </div>

                <div class="tab-pane fade" id="finished-pane" role="tabpanel">
                    @include('dashboard.exchange.coinex_spot_orders._orders_section', [
                        'title' => 'سفارش‌های تکمیل‌شده خرید',
                        'side' => 'buy',
                        'orders' => $finishedBuy['data'] ?? [],
                        'pagination' => $finishedBuy['pagination'] ?? [],
                        'cancellable' => false,
                        'market' => $market,
                        'fmt' => $fmt,
                        'fmtTs' => $fmtTs,
                    ])

                    @include('dashboard.exchange.coinex_spot_orders._orders_section', [
                        'title' => 'سفارش‌های تکمیل‌شده فروش',
                        'side' => 'sell',
                        'orders' => $finishedSell['data'] ?? [],
                        'pagination' => $finishedSell['pagination'] ?? [],
                        'cancellable' => false,
                        'market' => $market,
                        'fmt' => $fmt,
                        'fmtTs' => $fmtTs,
                    ])
                </div>
            </div>
        @endif

        <div class="modal fade coinex-order-lookup-modal" id="coinex-order-lookup-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header" id="coinex-lookup-modal-header">
                        <div>
                            <h5 class="modal-title mb-1" id="coinex-lookup-modal-title">جزئیات سفارش</h5>
                            <div class="d-flex flex-wrap align-items-center gap-2" id="coinex-lookup-modal-meta"></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="detail-grid mb-4" id="coinex-lookup-detail-grid"></div>

                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                            <h6 class="mb-0">معاملات این سفارش (Fills)</h6>
                            <span class="badge bg-label-primary" id="coinex-lookup-deals-count">0</span>
                        </div>
                        <div id="coinex-lookup-deals-wrap"></div>

                        <div class="accordion mt-4" id="coinex-lookup-raw-accordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#coinex-lookup-raw-json">
                                        پاسخ خام CoinEx (JSON)
                                    </button>
                                </h2>
                                <div id="coinex-lookup-raw-json" class="accordion-collapse collapse"
                                    data-bs-parent="#coinex-lookup-raw-accordion">
                                    <div class="accordion-body">
                                        <pre class="raw-json" id="coinex-lookup-raw"></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">بستن</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@push('scripts')
    <script>
        (function () {
            function initCoinexOrderLookup() {
            const cancelUrl = @json(route('admin.ref-exchange.coinex-spot-orders.cancel'));
            const lookupUrl = @json(route('admin.ref-exchange.coinex-spot-orders.lookup'));
            const csrfToken = @json(csrf_token());
            const initialOrderId = @json(request('order_id'));

            const fieldLabels = {
                order_id: 'Order ID',
                market: 'بازار',
                market_type: 'نوع بازار',
                ccy: 'ارز',
                side: 'سمت',
                type: 'نوع سفارش',
                amount: 'مقدار',
                price: 'قیمت',
                unfilled_amount: 'باقی‌مانده',
                filled_amount: 'پر شده',
                filled_value: 'ارزش پرشده',
                client_id: 'Client ID',
                base_fee: 'کارمزد پایه',
                quote_fee: 'کارمزد نقل‌قول',
                discount_fee: 'کارمزد تخفیف',
                maker_fee_rate: 'نرخ Maker',
                taker_fee_rate: 'نرخ Taker',
                last_fill_amount: 'آخرین مقدار پرشده',
                last_filled_amount: 'آخرین مقدار پرشده',
                last_fill_price: 'آخرین قیمت پرشده',
                last_filled_price: 'آخرین قیمت پرشده',
                created_at: 'زمان ایجاد',
                updated_at: 'زمان بروزرسانی',
                status: 'وضعیت',
            };

            const numericKeys = [
                'amount', 'price', 'unfilled_amount', 'filled_amount', 'filled_value',
                'base_fee', 'quote_fee', 'discount_fee', 'maker_fee_rate', 'taker_fee_rate',
                'last_fill_amount', 'last_filled_amount', 'last_fill_price', 'last_filled_price',
            ];

            const statusLabels = {
                open: 'باز',
                part_deal: 'بخشی پر شده',
                filled: 'تکمیل‌شده',
                canceled: 'لغو شده',
                cancelled: 'لغو شده',
                finish: 'تکمیل‌شده',
                pending: 'در انتظار',
            };

            function toast(text, ok) {
                if (typeof Toastify === 'undefined') {
                    window.alert(text);
                    return;
                }

                Toastify({
                    text: text,
                    duration: ok ? 3000 : 5000,
                    close: true,
                    gravity: 'top',
                    position: 'right',
                    stopOnFocus: true,
                    style: { background: ok ? '#28C76F' : '#EA5455' },
                }).showToast();
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function fmtNum(value) {
                if (value === null || value === undefined || value === '') {
                    return '—';
                }
                const num = Number(value);
                if (Number.isNaN(num)) {
                    return String(value);
                }
                return num.toLocaleString('en-US', { maximumFractionDigits: 8 });
            }

            function fmtTs(value) {
                const ms = Number(value);
                if (!ms) {
                    return '—';
                }
                const date = new Date(ms);
                if (Number.isNaN(date.getTime())) {
                    return '—';
                }
                const pad = (n) => String(n).padStart(2, '0');
                return date.getFullYear() + '/' + pad(date.getMonth() + 1) + '/' + pad(date.getDate())
                    + ' ' + pad(date.getHours()) + ':' + pad(date.getMinutes()) + ':' + pad(date.getSeconds());
            }

            function sideLabel(side) {
                return side === 'buy' ? 'خرید' : (side === 'sell' ? 'فروش' : (side || '—'));
            }

            function statusLabel(status) {
                if (!status) {
                    return '—';
                }
                return statusLabels[status] || status;
            }

            function fieldValue(key, order) {
                const value = order[key];
                if (value === null || value === undefined || value === '') {
                    return null;
                }
                if (key === 'created_at' || key === 'updated_at') {
                    return fmtTs(value);
                }
                if (key === 'side') {
                    return sideLabel(value);
                }
                if (key === 'status') {
                    return statusLabel(value);
                }
                if (key === 'price' && (Number(value) === 0 || String(order.type || '').toLowerCase() === 'market')) {
                    const filledAmount = Number(order.filled_amount || 0);
                    const filledValue = Number(order.filled_value || 0);
                    if (filledAmount > 0 && filledValue > 0) {
                        return fmtNum(filledValue / filledAmount);
                    }
                    return 'بازار';
                }
                if (numericKeys.indexOf(key) !== -1) {
                    return fmtNum(value);
                }
                return String(value);
            }

            function renderDetails(order) {
                const grid = document.getElementById('coinex-lookup-detail-grid');
                const known = Object.keys(fieldLabels);
                const keys = known.concat(Object.keys(order).filter((key) => known.indexOf(key) === -1));
                let html = '';

                keys.forEach((key) => {
                    if (!Object.prototype.hasOwnProperty.call(order, key)) {
                        return;
                    }
                    const display = fieldValue(key, order);
                    if (display === null) {
                        return;
                    }
                    html += '<div class="detail-item"><span class="label">'
                        + escapeHtml(fieldLabels[key] || key)
                        + '</span><span class="value">' + escapeHtml(display) + '</span></div>';
                });

                grid.innerHTML = html || '<div class="text-muted">فیلدی برای نمایش وجود ندارد.</div>';
            }

            function renderDeals(deals, dealsError) {
                const wrap = document.getElementById('coinex-lookup-deals-wrap');
                const countEl = document.getElementById('coinex-lookup-deals-count');
                const list = Array.isArray(deals) ? deals : [];
                countEl.textContent = String(list.length);

                if (dealsError) {
                    wrap.innerHTML = '<div class="alert alert-warning mb-0">' + escapeHtml(dealsError) + '</div>';
                    return;
                }

                if (!list.length) {
                    wrap.innerHTML = '<div class="empty-state py-3"><i class="fa-light fa-inbox fa-2x mb-2"></i><div>معامله‌ای برای این سفارش ثبت نشده است.</div></div>';
                    return;
                }

                let rows = '';
                list.forEach((deal) => {
                    rows += '<tr>'
                        + '<td class="fw-semibold">#' + escapeHtml(deal.deal_id ?? '—') + '</td>'
                        + '<td>' + escapeHtml(sideLabel(deal.side)) + '</td>'
                        + '<td>' + escapeHtml(fmtNum(deal.amount)) + '</td>'
                        + '<td>' + escapeHtml(fmtNum(deal.price)) + '</td>'
                        + '<td>' + escapeHtml(deal.role || '—') + '</td>'
                        + '<td>' + escapeHtml(fmtNum(deal.fee)) + ' ' + escapeHtml(deal.fee_ccy || '') + '</td>'
                        + '<td>' + escapeHtml(fmtTs(deal.created_at)) + '</td>'
                        + '</tr>';
                });

                wrap.innerHTML = '<div class="table-responsive"><table class="table table-hover align-middle mb-0">'
                    + '<thead><tr><th>Deal ID</th><th>سمت</th><th>مقدار</th><th>قیمت</th><th>نقش</th><th>کارمزد</th><th>زمان</th></tr></thead>'
                    + '<tbody>' + rows + '</tbody></table></div>';
            }

            function showLookupModal(payload) {
                const order = payload.order || {};
                const side = String(order.side || '').toLowerCase();
                const header = document.getElementById('coinex-lookup-modal-header');
                header.classList.remove('is-buy', 'is-sell');
                if (side === 'buy' || side === 'sell') {
                    header.classList.add('is-' + side);
                }

                document.getElementById('coinex-lookup-modal-title').textContent = 'جزئیات سفارش #' + (order.order_id || '');
                document.getElementById('coinex-lookup-modal-meta').innerHTML =
                    '<span class="status-pill">' + escapeHtml(payload.market || order.market || '') + '</span>'
                    + '<span class="status-pill">' + escapeHtml(sideLabel(side)) + '</span>'
                    + '<span class="status-pill">' + escapeHtml(order.type || '—') + '</span>'
                    + '<span class="status-pill">' + escapeHtml(statusLabel(order.status)) + '</span>';

                renderDetails(order);
                renderDeals(payload.deals, payload.deals_error);
                document.getElementById('coinex-lookup-raw').textContent = JSON.stringify({
                    order: order,
                    deals: payload.deals || [],
                }, null, 2);

                const modalEl = document.getElementById('coinex-order-lookup-modal');
                if (!modalEl || typeof bootstrap === 'undefined') {
                    toast('امکان نمایش جزئیات سفارش وجود ندارد.', false);
                    return;
                }

                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }

            function runOrderLookup() {
                const lookupBtn = document.getElementById('coinex-order-lookup-btn');
                const lookupInput = document.getElementById('lookup_order_id');
                const currencySelect = document.getElementById('currency_id');

                if (!lookupBtn || !lookupInput) {
                    toast('فرم جستجو در صفحه یافت نشد.', false);
                    return;
                }

                const currencyId = currencySelect ? currencySelect.value : '';
                const orderId = (lookupInput.value || '').trim();

                if (!/^\d+$/.test(orderId)) {
                    toast('شناسه سفارش باید یک عدد معتبر باشد.', false);
                    return;
                }

                const originalHtml = lookupBtn.innerHTML;
                lookupBtn.disabled = true;
                lookupBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> در حال جستجو...';

                const params = new URLSearchParams({ order_id: orderId });
                if (currencyId) {
                    params.set('currency_id', currencyId);
                }

                fetch(lookupUrl + '?' + params.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then(async (res) => {
                        const json = await res.json().catch(() => ({}));
                        if (!res.ok || !json.success) {
                            throw new Error(json.message || (json.errors ? Object.values(json.errors).flat().join(' ') : 'سفارش یافت نشد'));
                        }
                        showLookupModal(json);
                    })
                    .catch((err) => {
                        toast(err.message || 'خطا در دریافت سفارش', false);
                    })
                    .finally(() => {
                        lookupBtn.disabled = false;
                        lookupBtn.innerHTML = originalHtml;
                    });
            }

            document.addEventListener('click', function (e) {
                const lookupBtn = e.target.closest('#coinex-order-lookup-btn');
                if (lookupBtn) {
                    e.preventDefault();
                    runOrderLookup();
                    return;
                }

                const btn = e.target.closest('.cancel-coinex-order');
                if (!btn) return;

                const orderId = btn.dataset.orderId;
                const market = btn.dataset.market;

                if (!confirm('آیا از لغو سفارش #' + orderId + ' در بازار ' + market + ' مطمئن هستید؟\n\nاین عملیات قابل بازگشت نیست.')) {
                    return;
                }

                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                fetch(cancelUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        market: market,
                        order_id: Number(orderId),
                    }),
                })
                    .then(async (res) => {
                        const json = await res.json().catch(() => ({}));
                        if (!res.ok || !json.success) {
                            throw new Error(json.message || 'خطا در لغو سفارش');
                        }
                        toast(json.message || 'سفارش لغو شد.', true);
                        setTimeout(() => window.location.reload(), 700);
                    })
                    .catch((err) => {
                        toast(err.message || 'خطا در لغو سفارش', false);
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                    });
            });

            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter') {
                    return;
                }

                const lookupInput = document.getElementById('lookup_order_id');
                if (!lookupInput || e.target !== lookupInput) {
                    return;
                }

                e.preventDefault();
                runOrderLookup();
            });

            if (initialOrderId && /^\d+$/.test(String(initialOrderId))) {
                const lookupInput = document.getElementById('lookup_order_id');
                if (lookupInput) {
                    lookupInput.value = String(initialOrderId);
                    runOrderLookup();
                }
            }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initCoinexOrderLookup);
            } else {
                initCoinexOrderLookup();
            }
        })();
    </script>
@endpush
