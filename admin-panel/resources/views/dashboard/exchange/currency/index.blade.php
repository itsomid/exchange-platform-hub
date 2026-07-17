@extends('dashboard.layout.master')
@section('title', 'مدیریت کوین ها')
@section('content')

    {{-- Statistics Cards --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card ">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small mb-1">مجموع کوین‌ها</div>
                            <h3 class="mb-0 fw-bold">{{ $totalCount }}</h3>
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
        <div class="col-sm-6 col-xl-3">
            <div class="card ">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small mb-1">کوین‌های فعال</div>
                            <h3 class="mb-0 fw-bold text-success">{{ $activeCount }}</h3>
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
        <div class="col-sm-6 col-xl-3">
            <div class="card ">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small mb-1">کوین‌های غیرفعال</div>
                            <h3 class="mb-0 fw-bold text-warning">{{ $inactiveCount }}</h3>
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
        <div class="col-sm-6 col-xl-3">
            <div class="card ">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small mb-1">بدون شبکه</div>
                            <h3 class="mb-0 fw-bold text-danger">{{ $noChainsCount }}</h3>
                        </div>
                        <div class="avatar avatar-md">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="fa-solid fa-link-slash fa-lg"></i>
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
            <div id="filterForm" class="row g-3 align-items-end">

                <div class="col-md-4">
                    <label class="form-label" for="search">
                        <i class="fa-light fa-magnifying-glass me-1"></i>
                        جست‌وجو (نام / سیمبول)
                    </label>
                    <div class="position-relative">
                        <input type="text" id="search" name="search" class="form-control currency-filter pe-5"
                            placeholder="مثال: Bitcoin یا BTC ..." value="{{ request('search') }}" autocomplete="off">
                        <span id="currencySearchLoading"
                            class="spinner-border spinner-border-sm text-primary position-absolute top-50 end-0 translate-middle-y me-3 d-none"
                            role="status" aria-label="در حال جست‌وجو"></span>
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="chain">
                        <i class="fa-light fa-link me-1"></i>
                        شبکه
                    </label>
                    <select id="chain" name="chain" class="form-select currency-filter">
                        <option value="">همه شبکه‌ها</option>
                        @foreach ($availableChains as $chainValue)
                            <option value="{{ $chainValue }}" {{ request('chain') == $chainValue ? 'selected' : '' }}>
                                {{ $chainValue }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="status">
                        <i class="fa-light fa-toggle-on me-1"></i>
                        وضعیت
                    </label>
                    <select id="status" name="status" class="form-select currency-filter">
                        <option value="" {{ request('status') === '' ? 'selected' : '' }}>همه</option>
                        <option value="active" {{ request('status', 'active') == 'active' ? 'selected' : '' }}>فعال</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>غیرفعال</option>
                        <option value="no_chains" {{ request('status') == 'no_chains' ? 'selected' : '' }}>بدون شبکه
                        </option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="deposit">
                        <i class="fa-light fa-circle-arrow-down me-1"></i>
                        وضعیت واریز
                    </label>
                    <select id="deposit" name="deposit" class="form-select currency-filter">
                        <option value="">همه</option>
                        <option value="enabled" {{ request('deposit') == 'enabled' ? 'selected' : '' }}>فعال</option>
                        <option value="disabled" {{ request('deposit') == 'disabled' ? 'selected' : '' }}>غیرفعال</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="withdraw">
                        <i class="fa-light fa-circle-arrow-up me-1"></i>
                        وضعیت برداشت
                    </label>
                    <select id="withdraw" name="withdraw" class="form-select currency-filter">
                        <option value="">همه</option>
                        <option value="enabled" {{ request('withdraw') == 'enabled' ? 'selected' : '' }}>فعال</option>
                        <option value="disabled" {{ request('withdraw') == 'disabled' ? 'selected' : '' }}>غیرفعال</option>
                    </select>
                </div>

                <div class="col-12 d-flex justify-content-end">
                    <button type="button" id="clearFilters" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-light fa-xmark me-1"></i>
                        پاک کردن فیلترها
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Currency Table (AJAX target) --}}
    <div id="currencyTableContainer" class="position-relative">
        <div id="currencyTableContent">
            @include('dashboard.exchange.currency._table')
        </div>
        <div id="currencyTableLoading"
            class="position-absolute top-0 start-0 w-100 h-100 d-none align-items-start justify-content-center pt-5"
            style="z-index: 10; min-height: 180px; background: rgba(var(--bs-body-bg-rgb), 0.72); backdrop-filter: blur(2px);">
            <div class="d-flex align-items-center gap-3 bg-body rounded-3 shadow-sm border px-4 py-3 mt-4">
                <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                <span class="fw-semibold text-primary">در حال جست‌وجو و بروزرسانی لیست...</span>
            </div>
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
        (function() {
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
                return condition ?
                    `<span class="badge bg-label-success">${trueLabel}</span>` :
                    `<span class="badge bg-label-danger">${falseLabel}</span>`;
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
                const changePct = price.price_change_percentage !== null ?
                    `<span class="${changeClass} fw-semibold"><i class="fa-light ${changeIcon} me-1"></i>${parseFloat(price.price_change_percentage).toFixed(2)}%</span>` :
                    '—';

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
                    const chainLabel = typeof chain.chain === 'object' ? chain.chain.value ?? chain.chain :
                        chain.chain;
                    const blockchainLabel = typeof chain.blockchain_name === 'object' ? chain.blockchain_name
                        .value ?? chain.blockchain_name : chain.blockchain_name;
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

            document.addEventListener('click', function(e) {
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
                        document.getElementById('price-content').innerHTML = renderPrice(data.price, c
                            .symbol);
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

    {{-- Dynamic (AJAX) search / filter / pagination --}}
    {{--
        NOTE: A Vue app is mounted on #app (see resources/js/vue.conf.js) which
        re-renders the whole content on mount and destroys any directly-attached
        DOM listeners/node references. So everything below uses event delegation
        on `document` and re-queries the container to survive that re-render.
    --}}
    <script>
        (function() {
            'use strict';

            const baseUrl = @json(route('admin.currency.index'));
            let debounceTimer = null;
            let activeController = null;

            function getFilterEls() {
                return Array.from(document.querySelectorAll('.currency-filter'));
            }

            function buildParams() {
                const params = new URLSearchParams();
                getFilterEls().forEach(function(el) {
                    const value = (el.value || '').trim();
                    if (value !== '' || el.id === 'status') {
                        // Always send status (even empty = "all") so the server
                        // does not fall back to the default "active" filter.
                        params.set(el.name, value);
                    }
                });
                return params;
            }

            function setLoading(isLoading) {
                const container = document.getElementById('currencyTableContainer');
                const content = document.getElementById('currencyTableContent');
                const tableLoading = document.getElementById('currencyTableLoading');
                const searchLoading = document.getElementById('currencySearchLoading');
                const searchInput = document.getElementById('search');

                if (container) {
                    container.setAttribute('aria-busy', isLoading ? 'true' : 'false');
                }
                if (content) {
                    content.style.pointerEvents = isLoading ? 'none' : '';
                }
                if (tableLoading) {
                    tableLoading.classList.toggle('d-none', !isLoading);
                    tableLoading.classList.toggle('d-flex', isLoading);
                }
                if (searchLoading) {
                    searchLoading.classList.toggle('d-none', !isLoading);
                }
                if (searchInput) {
                    searchInput.setAttribute('aria-busy', isLoading ? 'true' : 'false');
                }
            }

            function load(url) {
                if (activeController) {
                    activeController.abort();
                }
                const requestController = new AbortController();
                activeController = requestController;

                setLoading(true);

                fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html'
                        },
                        credentials: 'same-origin',
                        signal: requestController.signal
                    })
                    .then(function(res) {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        return res.text();
                    })
                    .then(function(html) {
                        const content = document.getElementById('currencyTableContent');
                        if (content) content.innerHTML = html;
                        window.history.replaceState(null, '', url);
                    })
                    .catch(function(err) {
                        if (err.name === 'AbortError') return;
                        console.error('خطا در بارگذاری لیست کوین‌ها', err);
                    })
                    .finally(function() {
                        if (activeController === requestController) {
                            activeController = null;
                            setLoading(false);
                        }
                    });
            }

            function reload() {
                load(baseUrl + '?' + buildParams().toString());
            }

            document.addEventListener('input', function(e) {
                if (e.target && e.target.id === 'search') {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(reload, 300);
                }
            });

            document.addEventListener('change', function(e) {
                const el = e.target;
                if (el && el.classList && el.classList.contains('currency-filter') && el.id !== 'search') {
                    reload();
                }
            });

            document.addEventListener('click', function(e) {
                if (e.target.closest('#clearFilters')) {
                    getFilterEls().forEach(function(el) {
                        if (el.tagName === 'SELECT') {
                            el.value = el.id === 'status' ? 'active' : '';
                        } else {
                            el.value = '';
                        }
                    });
                    reload();
                    return;
                }

                const link = e.target.closest('#currencyTableContainer .pagination a.page-link');
                if (link && link.getAttribute('href')) {
                    e.preventDefault();
                    load(link.getAttribute('href'));
                }
            });
        })();
    </script>
@endpush
