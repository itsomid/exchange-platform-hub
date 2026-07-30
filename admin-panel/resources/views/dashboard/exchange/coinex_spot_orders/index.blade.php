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
    </div>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@push('scripts')
    <script>
        (function () {
            const cancelUrl = @json(route('admin.ref-exchange.coinex-spot-orders.cancel'));
            const csrfToken = @json(csrf_token());

            function toast(text, ok) {
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

            document.addEventListener('click', function (e) {
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
        })();
    </script>
@endpush
