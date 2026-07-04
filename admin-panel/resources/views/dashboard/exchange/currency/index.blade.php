@extends('dashboard.layout.master')
@section('title', 'مدیریت کوین ها')
@section('content')

    {{-- Statistics Cards --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-4">
            <div class="card ">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small mb-1">مجموع کوین‌ها</div>
                            <h3 class="mb-0 fw-bold">{{ $currencies->count() }}</h3>
                        </div>
                        <div class="avatar avatar-md">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="fa-solid fa-coins fa-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-4">
            <div class="card ">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small mb-1">کوین‌های فعال</div>
                            <h3 class="mb-0 fw-bold text-success">{{ $currenciesWithChainsCount }}</h3>
                        </div>
                        <div class="avatar avatar-md">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="fa-solid fa-circle-check fa-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-4">
            <div class="card ">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small mb-1">کوین‌های غیرفعال</div>
                            <h3 class="mb-0 fw-bold text-warning">{{ $currenciesWithoutChainsCount }}</h3>
                        </div>
                        <div class="avatar avatar-md">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="fa-solid fa-circle-xmark fa-lg"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search & Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3 align-items-end" action="{{ route('admin.currency.index') }}" method="get">

                <div class="col-md-4">
                    <label class="form-label" for="search">
                        <i class="fa-light fa-magnifying-glass me-1"></i>
                        جست‌وجو (نام / سیمبول)
                    </label>
                    <input type="text" id="search" name="search" class="form-control" placeholder="مثال: Bitcoin یا BTC ..."
                        value="{{ request('search') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="chain">
                        <i class="fa-light fa-link me-1"></i>
                        شبکه
                    </label>
                    <select id="chain" name="chain" class="form-select">
                        <option value="">همه شبکه‌ها</option>
                        @foreach($availableChains as $chainValue)
                            <option value="{{ $chainValue }}" {{ request('chain') == $chainValue ? 'selected' : '' }}>
                                {{ $chainValue }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="status">
                        <i class="fa-light fa-toggle-on me-1"></i>
                        وضعیت
                    </label>
                    <select id="status" name="status" class="form-select">
                        <option value="">همه</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>فعال (دارای شبکه)
                        </option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>غیرفعال (بدون شبکه)
                        </option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fa-light fa-filter me-1"></i>
                        فیلتر
                    </button>
                    <a href="{{ route('admin.currency.index') }}" class="btn btn-outline-secondary" title="پاک کردن فیلتر">
                        <i class="fa-light fa-xmark"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Currency Table --}}
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between py-3">
            <h5 class="mb-0">
                <i class="fa-light fa-list me-2"></i>
                لیست کوین‌ها
                <span class="badge bg-label-secondary ms-2">{{ $currencies->count() }}</span>
            </h5>
            <a href="{{ route('admin.currency.create') }}" class="btn btn-primary btn-sm">
                <i class="fa fa-plus me-1"></i>
                افزودن کوین جدید
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-center">
                        <th class="fw-semibold" style="width: 50px">#</th>
                        <th class="fw-semibold text-start" style="min-width: 200px">کوین</th>
                        <th class="fw-semibold">شبکه‌ها</th>
                        <th class="fw-semibold">کارمزد برداشت صرافی</th>
                        <th class="fw-semibold">کارمزد شبکه</th>
                        <th class="fw-semibold">وضعیت</th>
                        <th class="fw-semibold" style="width: 100px">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($currencies as $currency)
                        <tr>
                            <td class="text-center text-muted">{{ $currency->id }}</td>

                            {{-- Coin Info (combined avatar + name + symbol) --}}
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ asset($currency->coinLogo()) }}" class="rounded-circle" width="40" height="40"
                                        alt="{{ $currency->symbol }}">
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="fw-semibold">{{ $currency->name }}</div>
                                            @if(!$currency->is_active)
                                                <span class="badge bg-label-warning">غیرفعال</span>
                                            @endif
                                        </div>
                                        <small class="text-muted">{{ $currency->symbol }}</small>
                                    </div>
                                </div>
                            </td>

                            {{-- Chains --}}
                            <td class="text-center">
                                @if($currency->chains->count())
                                    @foreach($currency->chains as $chain)
                                        <div class="d-flex flex-wrap justify-content-center gap-1 px-2 py-1 ">

                                            <span class="badge bg-label-primary d-block">{{ $chain->chain }}</span>

                                        </div>
                                    @endforeach
                                @else
                                    <span class="badge bg-label-danger">بدون شبکه</span>
                                @endif
                            </td>

                            {{-- Exchange Withdrawal Fee --}}
                            <td class="text-center">
                                @if($currency->chains->count())
                                    <div class="d-flex flex-column gap-1 align-items-center">
                                        @foreach($currency->chains as $chain)
                                            <div class="d-flex align-items-center gap-1 rounded px-2 py-1 bg-label-secondary"
                                                style="font-size: 0.78rem;">
                                                <span class="text-muted">{{ $currency->symbol }}</span>
                                                <span
                                                    class="fw-semibold font-number text-dark mb-1">{{ formatNumberTrimZeros($chain->exchange_withdrawal_fee, $currency->amount_precision) }}</span>

                                                <span class="badge bg-label-primary"
                                                    style="font-size: 0.68rem;">{{ $chain->chain }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Network Fee --}}
                            <td class="text-center">
                                @if($currency->chains->count())
                                    <div class="d-flex flex-column gap-1 align-items-center">
                                        @foreach($currency->chains as $chain)
                                            <div class="d-flex align-items-center gap-1 rounded px-2 py-1 bg-label-secondary"
                                                style="font-size: 0.78rem;">
                                                <span class="text-muted">{{ $currency->symbol }}</span>
                                                <span
                                                    class="fw-semibold font-number text-dark mb-1">{{ formatNumberTrimZeros($chain->network_fee, $currency->amount_precision) }}</span>
                                                <span class="badge bg-label-info" style="font-size: 0.68rem;">{{ $chain->chain }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="text-center">
                                @if(!$currency->is_active)
                                    <span class="badge bg-label-warning">کوین غیرفعال</span>
                                @elseif($currency->chains->count())
                                    <div class="d-flex flex-column gap-2 align-items-center">
                                        @foreach($currency->chains as $chain)
                                            <div class="d-flex rounded border px-2 py-1" style="min-width: 130px; font-size: 0.75rem;">
                                                <div class="fw-semibold text-muted me-1 border-end pe-1" style="font-size: 0.7rem;">
                                                    {{ $chain->chain }}
                                                </div>
                                                <div class="d-flex align-items-center justify-content-between gap-2">
                                                    <span class="d-flex align-items-center gap-1">
                                                        <i
                                                            class="fa-solid fa-circle-arrow-down {{ $chain->deposit_enabled ? 'text-success' : 'text-danger' }}"></i>
                                                        <span
                                                            class="{{ $chain->deposit_enabled ? 'text-success' : 'text-danger' }}">واریز</span>
                                                    </span>
                                                    <span class="d-flex align-items-center gap-1">
                                                        <i
                                                            class="fa-solid fa-circle-arrow-up {{ $chain->withdraw_enabled ? 'text-success' : 'text-danger' }}"></i>
                                                        <span
                                                            class="{{ $chain->withdraw_enabled ? 'text-success' : 'text-danger' }}">برداشت</span>
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="badge bg-label-danger">بدون شبکه</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <a class="btn btn-icon btn-sm btn-outline-primary"
                                        href="{{ route('admin.currency.edit', ['currency' => $currency->id]) }}" title="ویرایش">
                                        <i class="fa-light fa-pen-to-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-icon btn-sm btn-outline-secondary btn-currency-preview"
                                        data-currency-id="{{ $currency->id }}"
                                        data-currency-url="{{ route('admin.currency.show', ['currency' => $currency->id]) }}"
                                        title="مشاهده">
                                        <i class="fa-light fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fa-light fa-inbox fa-3x mb-3 d-block"></i>
                                    کوینی یافت نشد
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Currency Preview Modal --}}
    <div class="modal fade" id="currencyPreviewModal" tabindex="-1" aria-labelledby="currencyPreviewModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title d-flex align-items-center gap-2" id="currencyPreviewModalLabel">
                        <span id="modal-coin-logo-wrap"></span>
                        <span id="modal-coin-title">اطلاعات کوین</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    {{-- Loading State --}}
                    <div id="modal-loading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">در حال بارگذاری...</span>
                        </div>
                        <p class="text-muted mt-3 mb-0">در حال دریافت اطلاعات...</p>
                    </div>

                    {{-- Error State --}}
                    <div id="modal-error" class="d-none text-center py-5">
                        <i class="fa-light fa-triangle-exclamation fa-3x text-danger mb-3 d-block"></i>
                        <p class="text-danger mb-0">خطا در دریافت اطلاعات. لطفاً دوباره تلاش کنید.</p>
                    </div>

                    {{-- Content --}}
                    <div id="modal-content" class="d-none">

                        {{-- Price Section --}}
                        <div id="price-section" class="mb-4">
                            <h6 class="fw-bold text-primary mb-3 d-flex align-items-center gap-2">
                                <i class="fa-light fa-chart-line"></i>
                                اطلاعات قیمت
                            </h6>
                            <div id="price-content"></div>
                        </div>

                        <hr class="my-3">

                        {{-- Currency Info Section --}}
                        <div class="mb-4">
                            <h6 class="fw-bold text-primary mb-3 d-flex align-items-center gap-2">
                                <i class="fa-light fa-coins"></i>
                                اطلاعات کوین
                            </h6>
                            <div id="currency-info-content"></div>
                        </div>

                        <hr class="my-3" id="chains-divider">

                        {{-- Chains Section --}}
                        <div id="chains-section">
                            <h6 class="fw-bold text-primary mb-3 d-flex align-items-center gap-2">
                                <i class="fa-light fa-link"></i>
                                شبکه‌ها
                            </h6>
                            <div id="chains-content"></div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer border-top">
                    <a id="modal-edit-link" href="#" class="btn btn-primary btn-sm">
                        <i class="fa-light fa-pen-to-square me-1"></i>
                        ویرایش
                    </a>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">بستن</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            'use strict';

            let bsModal = null;
            function getModal() {
                if (!bsModal) {
                    bsModal = new bootstrap.Modal(document.getElementById('currencyPreviewModal'));
                }
                return bsModal;
            }

            function formatNumberTrimZeros(value, precision) {
                if (value === null || value === undefined) return '—';
                const num = parseFloat(value);
                if (isNaN(num)) return value;
                return num.toFixed(precision ?? 8).replace(/\.?0+$/, '');
            }

            function badge(condition, trueLabel, falseLabel) {
                return condition
                    ? `<span class="badge bg-label-success">${trueLabel}</span>`
                    : `<span class="badge bg-label-danger">${falseLabel}</span>`;
            }

            function infoRow(label, value) {
                return `
                                <div class="col-sm-6 col-lg-4">
                                    <div class="d-flex flex-column p-2 rounded border h-100" style="border-color: rgba(var(--bs-border-color-rgb), 0.5) !important;">
                                        <small class="text-muted mb-1" style="font-size:0.72rem;">${label}</small>
                                        <span class="fw-semibold">${value}</span>
                                    </div>
                                </div>`;
            }

            function renderPrice(price, symbol) {
                if (!price) {
                    return `<div class="alert alert-secondary py-2 mb-0">
                                            <i class="fa-light fa-circle-info me-2"></i>
                                            اطلاعات قیمتی برای این کوین موجود نیست.
                                        </div>`;
                }
                const changeClass = price.price_change_percentage >= 0 ? 'text-success' : 'text-danger';
                const changeIcon = price.price_change_percentage >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down';
                const changePct = price.price_change_percentage !== null
                    ? `<span class="${changeClass} fw-semibold"><i class="fa-light ${changeIcon} me-1"></i>${parseFloat(price.price_change_percentage).toFixed(2)}%</span>`
                    : '—';

                return `
                                <div class="row g-3">
                                    <div class="col-sm-6 col-lg-3">
                                        <div class="card border-0 bg-label-primary h-100">
                                            <div class="card-body py-3 px-3">
                                                <small class="text-muted d-block mb-1">قیمت فعلی (${price.market})</small>
                                                <div class="fw-bold font-number fs-6">${formatNumberTrimZeros(price.price, 2)} USDT</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-3">
                                        <div class="card border-0 bg-label-secondary h-100">
                                            <div class="card-body py-3 px-3">
                                                <small class="text-muted d-block mb-1">قیمت باز (24h)</small>
                                                <div class="fw-bold font-number fs-6">${formatNumberTrimZeros(price.open_price, 2)} USDT</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-2">
                                        <div class="card border-0 bg-label-secondary h-100">
                                            <div class="card-body py-3 px-3">
                                                <small class="text-muted d-block mb-1">تغییر (24h)</small>
                                                <div class="fw-bold font-number fs-6">${changePct}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-2">
                                        <div class="card border-0 bg-label-success h-100">
                                            <div class="card-body py-3 px-3">
                                                <small class="text-muted d-block mb-1">قیمت فروش صرافی</small>
                                                <div class="fw-bold font-number fs-6">${formatNumberTrimZeros(price.exchange_sell_price, 2)}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-2">
                                        <div class="card border-0 bg-label-info h-100">
                                            <div class="card-body py-3 px-3">
                                                <small class="text-muted d-block mb-1">قیمت خرید صرافی</small>
                                                <div class="fw-bold font-number fs-6">${formatNumberTrimZeros(price.exchange_buy_price, 2)}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
            }

            function renderCurrencyInfo(c) {
                return `
                                <div class="row g-2">
                                    ${infoRow('نام', c.name)}
                                    ${infoRow('نام فارسی', c.persian_name ?? '—')}
                                    ${infoRow('سیمبول', `<span class="badge bg-label-primary fs-6">${c.symbol}</span>`)}
                                    ${infoRow('دقت قیمت', c.price_precision ?? '—')}
                                    ${infoRow('دقت مقدار', c.amount_precision ?? '—')}
                                    ${infoRow('حداکثر برداشت خودکار', `<span class="font-number">${formatNumberTrimZeros(c.max_auto_withdraw_amount, 8)}</span>`)}
                                    ${infoRow('انتقال داخلی', badge(c.inter_transfer_enabled, 'فعال', 'غیرفعال'))}
                                    ${infoRow('برداشت از صرافی مرجع', badge(c.ref_exchange_withdrawal_enabled, 'فعال', 'غیرفعال'))}
                                    ${infoRow('فاصله زمانی برداشت (دقیقه)', c.ref_exchange_withdrawal_interval_minutes !== null ? c.ref_exchange_withdrawal_interval_minutes : '<span class="text-muted fst-italic">تنظیم سراسری</span>')}
                                    ${infoRow('حداقل تعداد خرید برای برداشت', c.ref_exchange_withdrawal_min_count !== null ? c.ref_exchange_withdrawal_min_count : '<span class="text-muted fst-italic">تنظیم سراسری</span>')}
                                    ${infoRow('ایجاد در', new Date(c.created_at).toLocaleString('fa-IR'))}
                                    ${infoRow('آخرین بروزرسانی', new Date(c.updated_at).toLocaleString('fa-IR'))}
                                </div>`;
            }

            function renderChains(chains, currency) {
                if (!chains || chains.length === 0) {
                    return `<div class="alert alert-warning py-2 mb-0">
                                            <i class="fa-light fa-triangle-exclamation me-2"></i>
                                            هیچ شبکه‌ای برای این کوین تعریف نشده است.
                                        </div>`;
                }

                return chains.map(chain => {
                    const chainLabel = typeof chain.chain === 'object' ? chain.chain.value ?? chain.chain : chain.chain;
                    const blockchainLabel = typeof chain.blockchain_name === 'object' ? chain.blockchain_name.value ?? chain.blockchain_name : chain.blockchain_name;
                    return `
                                <div class="card border mb-3">
                                    <div class="card-header py-2 px-3 d-flex align-items-center justify-content-between">
                                        <span class="fw-bold d-flex align-items-center gap-2">
                                            <span class="badge bg-label-primary">${chainLabel}</span>
                                            <small class="text-muted">${blockchainLabel ?? ''}</small>
                                        </span>
                                        <div class="d-flex gap-2">
                                            <span class="badge ${chain.deposit_enabled ? 'bg-label-success' : 'bg-label-danger'}">
                                                <i class="fa-solid fa-circle-arrow-down me-1"></i>واریز
                                            </span>
                                            <span class="badge ${chain.withdraw_enabled ? 'bg-label-success' : 'bg-label-danger'}">
                                                <i class="fa-solid fa-circle-arrow-up me-1"></i>برداشت
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-2">
                                            ${infoRow('کارمزد برداشت صرافی', `<span class="font-number">${formatNumberTrimZeros(chain.exchange_withdrawal_fee, 8)} ${currency.symbol}</span>`)}
                                            ${infoRow('کارمزد شبکه', `<span class="font-number">${formatNumberTrimZeros(chain.network_fee, 8)} ${currency.symbol}</span>`)}
                                            ${infoRow('مجموع کارمزد', `<span class="font-number text-warning fw-bold">${formatNumberTrimZeros(chain.total_withdrawal_fee, 8)} ${currency.symbol}</span>`)}
                                            ${infoRow('حداقل واریز', `<span class="font-number">${formatNumberTrimZeros(chain.min_deposit_amount, 8)} ${currency.symbol}</span>`)}
                                            ${infoRow('حداقل برداشت', `<span class="font-number">${formatNumberTrimZeros(chain.min_withdraw_amount, 8)} ${currency.symbol}</span>`)}
                                            ${infoRow('تأخیر واریز (دقیقه)', chain.deposit_delay_minutes)}
                                            ${infoRow('تأییدیه‌های امن', chain.safe_confirmations)}
                                            ${infoRow('کوین پایه', badge(chain.is_base_coin, 'بله', 'خیر'))}
                                            ${infoRow('نیاز به ممو', badge(chain.is_memo_required_for_deposit, 'بله', 'خیر'))}
                                            ${chain.contract_address ? infoRow('آدرس قرارداد', `<span class="font-number text-truncate d-block" style="max-width:200px;" title="${chain.contract_address}">${chain.contract_address}</span>`) : ''}
                                        </div>
                                    </div>
                                </div>`;
                }).join('');
            }

            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-currency-preview');
                if (!btn) return;

                const url = btn.dataset.currencyUrl;

                // Reset modal state
                document.getElementById('modal-loading').classList.remove('d-none');
                document.getElementById('modal-error').classList.add('d-none');
                document.getElementById('modal-content').classList.add('d-none');
                document.getElementById('modal-coin-logo-wrap').innerHTML = '';
                document.getElementById('modal-coin-title').textContent = 'اطلاعات کوین';

                bsModal = getModal();
                bsModal.show();

                fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''
                    }
                })
                    .then(res => {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        return res.json();
                    })
                    .then(data => {
                        const c = data.currency;

                        // Header
                        document.getElementById('modal-coin-logo-wrap').innerHTML =
                            `<img src="${data.logo_url}" class="rounded-circle" width="30" height="30" alt="${c.symbol}" onerror="this.onerror=null;this.src='/images/coins/default.png'">`;
                        document.getElementById('modal-coin-title').textContent = `${c.name} (${c.symbol})`;

                        // Edit link
                        document.getElementById('modal-edit-link').href = data.edit_url;

                        // Sections
                        document.getElementById('price-content').innerHTML = renderPrice(data.price, c.symbol);
                        document.getElementById('currency-info-content').innerHTML = renderCurrencyInfo(c);
                        document.getElementById('chains-content').innerHTML = renderChains(c.chains, c);

                        document.getElementById('modal-loading').classList.add('d-none');
                        document.getElementById('modal-content').classList.remove('d-none');
                    })
                    .catch(() => {
                        document.getElementById('modal-loading').classList.add('d-none');
                        document.getElementById('modal-error').classList.remove('d-none');
                    });
            });
        })();
    </script>
@endpush
