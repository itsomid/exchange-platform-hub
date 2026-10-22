@extends('dashboard.layout.master')

@section('title', 'پله‌های فروش در انتظار ثبت — ربات معاملاتی')

@section('content')
    @php
        $totals = $lastRun['totals'] ?? null;
    @endphp

    {{-- Page header --}}
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-4">
        <div>
            <h4 class="mb-1">پله‌های فروش در انتظار ثبت</h4>
            <p class="text-muted mb-0" style="max-width: 56rem; line-height: 1.8;">
                Binance سفارش فروش Limit را فقط تا چند برابر میانگین قیمت اخیر هر ارز قبول می‌کند (فیلتر
                PERCENT_PRICE_BY_SIDE). پله‌هایی که قیمتشان بالاتر از این سقف است ثبت نمی‌شوند و اینجا «در انتظار ثبت»
                می‌مانند. سیستم هر دقیقه بررسی می‌کند و هر پله‌ای که زیر سقف آمد را در Binance ثبت می‌کند؛ اگر قیمت در این مدت
                از تارگت پله بگذرد، پله با Market فروخته و مثل یک پله پرشده تسویه می‌شود.
            </p>
        </div>
        <div class="card mb-0">
            <div class="card-body py-2 px-3">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="autoPlaceToggle"
                        data-url="{{ route('admin.bot.waiting-sells.auto-place') }}" @checked($autoPlace)>
                    <label class="form-check-label fw-semibold" for="autoPlaceToggle">ثبت خودکار (هر دقیقه)</label>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-start justify-content-between">
                    <div>
                        <span class="d-block text-muted mb-1">پله‌های در انتظار ثبت</span>
                        <h4 class="mb-0 font-number">{{ $rows->sum('tiers') }}</h4>
                        <small class="text-muted">{{ $rows->count() }} ارز</small>
                    </div>
                    <span class="badge bg-label-primary rounded p-2"><i class="fa-light fa-hourglass-half fa-lg"></i></span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-start justify-content-between">
                    <div>
                        <span class="d-block text-muted mb-1">کاربران / سفارش‌ها</span>
                        <h4 class="mb-0 font-number">{{ $rows->sum('users') }} / {{ $rows->sum('orders') }}</h4>
                        <small class="text-muted">درگیر پله‌های در انتظار</small>
                    </div>
                    <span class="badge bg-label-info rounded p-2"><i class="fa-light fa-users fa-lg"></i></span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-start justify-content-between">
                    <div>
                        <span class="d-block text-muted mb-1">آخرین اجرای خودکار</span>
                        @if ($lastRun)
                            <h6 class="mb-0 font-number">
                                {{ \App\Helpers\DateFormatter::convertToPersianDate(\Illuminate\Support\Carbon::parse($lastRun['at']), '%Y/%m/%d H:i') }}
                            </h6>
                            <small class="text-muted">
                                @if (!empty($lastRun['disabled']))
                                    ثبت خودکار خاموش بود
                                @elseif ($totals)
                                    ثبت {{ $totals['placed'] }} · Market {{ $totals['completed'] }} · رد {{ $totals['rejected'] }}
                                @endif
                            </small>
                        @else
                            <h6 class="mb-0 text-muted">—</h6>
                            <small class="text-muted">اطلاعاتی از api-service دریافت نشد</small>
                        @endif
                    </div>
                    <span class="badge bg-label-success rounded p-2"><i class="fa-light fa-clock-rotate-left fa-lg"></i></span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-start justify-content-between">
                    <div>
                        <span class="d-block text-muted mb-1">وضعیت ثبت خودکار</span>
                        <h6 class="mb-0 {{ $autoPlace ? 'text-success' : 'text-danger' }}" id="autoPlaceState">
                            {{ $autoPlace ? 'فعال' : 'غیرفعال' }}</h6>
                        <small class="text-muted">دستی از دکمه‌های همین صفحه هم ممکن است</small>
                    </div>
                    <span class="badge bg-label-warning rounded p-2"><i class="fa-light fa-robot fa-lg"></i></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Main table card --}}
    <div class="card">
        <div class="card-header border-bottom">
            <div class="row g-3 align-items-end">
                <div class="col-lg-4 col-md-6">
                    <label class="form-label" for="currencySearch">جستجوی ارز</label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="currencySearch" class="form-control" autocomplete="off"
                            placeholder="نماد یا نام ارز، مثلاً IOST">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label" for="stateFilter">وضعیت</label>
                    <select id="stateFilter" class="form-select">
                        <option value="">همه ارزها</option>
                        <option value="error">دارای خطای ثبت</option>
                        <option value="clean">بدون خطا</option>
                    </select>
                </div>
                <div class="col-lg-5 col-md-12 d-flex align-items-end justify-content-lg-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="clearFilters">
                        <i class="fas fa-times me-1"></i> حذف فیلترها
                    </button>
                </div>
            </div>
            <div class="alert alert-info d-none align-items-center flex-wrap mt-3 mb-0" id="activeFilters">
                <i class="fas fa-info-circle me-2"></i>
                <span class="me-2">فیلترهای فعال:</span>
                <div class="d-flex flex-wrap gap-1" id="activeFiltersList"></div>
                <span class="ms-auto small" id="filterResultCount"></span>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="waitingTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">ارز</th>
                            <th class="text-nowrap text-center">کاربران / سفارش‌ها</th>
                            <th class="text-nowrap">پله‌ها / مقدار</th>
                            <th class="text-nowrap">قیمت لحظه‌ای</th>
                            <th class="text-nowrap">نزدیک‌ترین تارگت</th>
                            <th class="text-nowrap">در انتظار از</th>
                            <th class="text-nowrap">آخرین بررسی</th>
                            <th class="pe-4 text-center">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr data-search="{{ strtolower($row->symbol . ' ' . $row->currency->name . ' ' . $row->currency->persian_name) }}"
                                data-state="{{ $row->last_error ? 'error' : 'clean' }}">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar avatar-sm">
                                            <img src="{{ $row->currency->coinLogo() }}" alt="" class="rounded-circle"
                                                width="32" height="32">
                                        </div>
                                        <div>
                                            <strong class="lh-1">{{ $row->symbol }}</strong>
                                            <div><small
                                                    class="text-muted">{{ $row->currency->persian_name ?: $row->currency->name }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="font-number text-center">{{ $row->users }} / {{ $row->orders }}</td>
                                <td class="font-number">
                                    <span class="badge bg-label-primary">{{ $row->tiers }} پله</span>
                                    <div><small>{{ formatNumberTrimZeros($row->amount, 8) }} {{ $row->symbol }}</small></div>
                                </td>
                                <td class="font-number">
                                    @if ($row->price)
                                        {{ formatNumberTrimZeros($row->price, 8) }}
                                    @else
                                        <span class="text-muted" title="قیمت لحظه‌ای در کش موجود نیست">—</span>
                                    @endif
                                </td>
                                <td class="font-number">
                                    {{ formatNumberTrimZeros($row->lowest_target, 8) }}
                                    @if ($row->lowest_above !== null)
                                        <div><small class="text-muted" dir="ltr">+{{ number_format($row->lowest_above, 2) }}٪</small>
                                            <small class="text-muted">از بازار</small></div>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $row->oldest ? \App\Helpers\DateFormatter::convertToPersianDate($row->oldest, '%Y/%m/%d H:i') : '—' }}</small>
                                </td>
                                <td>
                                    <small>{{ $row->last_check ? \App\Helpers\DateFormatter::convertToPersianDate($row->last_check, '%Y/%m/%d H:i') : '—' }}</small>
                                    @if ($row->last_error)
                                        <i class="fas fa-exclamation-circle text-danger ms-1" title="{{ $row->last_error }}"></i>
                                    @endif
                                    <div><small class="text-muted">{{ $row->attempts }} تلاش</small></div>
                                </td>
                                <td class="pe-4 text-center text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary js-waiting-details"
                                        data-symbol="{{ $row->symbol }}"
                                        data-details-url="{{ route('admin.bot.waiting-sells.details', $row->currency->id) }}"
                                        data-place-url="{{ route('admin.bot.waiting-sells.place-currency', $row->currency->id) }}">
                                        <i class="fa-light fa-list me-1"></i> جزئیات
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary js-waiting-place-currency"
                                        data-symbol="{{ $row->symbol }}"
                                        data-place-url="{{ route('admin.bot.waiting-sells.place-currency', $row->currency->id) }}">
                                        <i class="fa-light fa-paper-plane me-1"></i> بررسی و ثبت الان
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="fa-light fa-circle-check fa-2x d-block mb-2 opacity-50"></i>
                                    هیچ پله فروشی در انتظار ثبت نیست.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="noFilterResult" class="d-none">
                            <td colspan="8" class="text-center text-muted py-4">ارزی با این مشخصات پیدا نشد.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Per-coin details --}}
    <div class="modal fade" id="waitingDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="waitingDetailsTitle">پله‌های در انتظار</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="waitingDetailsBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">بستن</button>
                    <button type="button" class="btn btn-primary" id="waitingDetailsPlace">
                        <i class="fa-light fa-paper-plane me-1"></i> بررسی و ثبت همه پله‌های قابل ثبت
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var csrf = '{{ csrf_token() }}';
            var placeTierUrl = @json(route('admin.bot.waiting-sells.place-tier', ['sellOrder' => '__ID__']));

            function toast(text, ok) {
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
            }

            function request(method, url, body) {
                return fetch(url, {
                    method: method,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: body ? JSON.stringify(body) : undefined
                }).then(function(r) {
                    return r.json().catch(function() {
                        return {
                            ok: false,
                            error: 'پاسخ نامعتبر از سرور (' + r.status + ')'
                        };
                    });
                });
            }

            function esc(v) {
                var d = document.createElement('div');
                d.textContent = v === null || v === undefined ? '' : String(v);
                return d.innerHTML;
            }

            function fmt(v, maxDec) {
                var n = parseFloat(v);
                if (!isFinite(n)) return '—';
                return n.toLocaleString('en-US', {
                    maximumFractionDigits: maxDec === undefined ? 8 : maxDec
                });
            }

            function pct(v) {
                var n = parseFloat(v);
                if (!isFinite(n)) return '—';
                return '<span class="font-number" dir="ltr">' + (n >= 0 ? '+' : '') + fmt(n, 2) + '٪</span>';
            }

            function when(iso) {
                return iso ? new Date(iso).toLocaleString('fa-IR') : '—';
            }

            function busy(btn, on) {
                if (on) {
                    btn.dataset.html = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                } else {
                    btn.disabled = false;
                    btn.innerHTML = btn.dataset.html;
                }
            }

            /* ── Auto placement toggle ── */
            var toggle = document.getElementById('autoPlaceToggle');
            var toggleState = document.getElementById('autoPlaceState');
            toggle.addEventListener('change', function() {
                toggle.disabled = true;
                request('POST', toggle.dataset.url, {
                    enabled: toggle.checked
                }).then(function(d) {
                    if (!d.ok) {
                        toggle.checked = !toggle.checked;
                        toast(d.error || d.message || 'تغییر وضعیت انجام نشد.', false);
                        return;
                    }
                    toggleState.textContent = d.enabled ? 'فعال' : 'غیرفعال';
                    toggleState.className = 'mb-0 ' + (d.enabled ? 'text-success' : 'text-danger');
                    toast(d.message, true);
                }).catch(function() {
                    toggle.checked = !toggle.checked;
                    toast('خطا در ارتباط با سرور.', false);
                }).finally(function() {
                    toggle.disabled = false;
                });
            });

            /* ── Place now (per coin) ── */
            function placeCurrency(url, symbol, btn) {
                if (!confirm('پله‌های در انتظار ' + symbol + ' بررسی و هر کدام که زیر سقف قیمت Binance است ثبت شود؟')) return;
                busy(btn, true);
                request('POST', url).then(function(d) {
                    toast(d.ok ? d.message : (d.error || 'ثبت انجام نشد.'), !!d.ok);
                    if (d.ok) setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                }).catch(function() {
                    toast('خطا در ارتباط با سرور.', false);
                }).finally(function() {
                    busy(btn, false);
                });
            }

            document.querySelectorAll('.js-waiting-place-currency').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    placeCurrency(btn.dataset.placeUrl, btn.dataset.symbol, btn);
                });
            });

            /* ── Details modal ── */
            var modalEl = document.getElementById('waitingDetailsModal');
            var modal = new bootstrap.Modal(modalEl);
            var bodyEl = document.getElementById('waitingDetailsBody');
            var titleEl = document.getElementById('waitingDetailsTitle');
            var placeAllBtn = document.getElementById('waitingDetailsPlace');
            var active = null;
            var changed = false;

            function renderDetails(d) {
                var sym = esc(d.currency.symbol);
                var c = d.ceiling;
                var html = '';

                if (d.block_reason) {
                    html += '<div class="alert alert-warning"><i class="fas fa-ban me-1"></i>' + esc(d.block_reason) + '</div>';
                }

                html += '<div class="alert ' + (c ? 'alert-info' : 'alert-secondary') + ' py-2 small">';
                if (c) {
                    html += '<div class="mb-1"><i class="fas fa-info-circle me-1"></i><strong>سقف قیمت فروش در Binance برای ' + sym + ':</strong> ' +
                        'حداکثر <span class="font-number">' + fmt(c.multiplier_up, 4) + '</span> برابر میانگین قیمت ' +
                        (c.avg_price_mins ? '<span class="font-number">' + c.avg_price_mins + '</span> دقیقه اخیر' : 'اخیر') +
                        ' (حداکثر ' + pct(c.max_percent_above) + ' بالاتر از بازار).</div>' +
                        '<div>' + (c.reference === 'avg' ? 'میانگین فعلی' : 'قیمت لحظه‌ای (میانگین Binance دریافت نشد)') +
                        ' <span class="font-number">' + fmt(c.reference_price) + '</span> USDT؛ سقف الان <strong class="font-number">' +
                        fmt(c.max_price) + '</strong> USDT و با حاشیه اطمینان، پله‌ها تا <strong class="font-number">' +
                        fmt(c.place_max_price) + '</strong> USDT ثبت می‌شوند. قیمت لحظه‌ای: <span class="font-number">' +
                        fmt(d.live_price) + '</span> USDT.</div>';
                } else {
                    html += '<i class="fas fa-info-circle me-1"></i>سقف قیمت Binance برای این ارز دریافت نشد؛ فقط پله‌هایی که ' +
                        'قیمت از تارگتشان گذشته (فروش Market) قابل اقدام‌اند.';
                }
                html += '</div>';

                var tiers = d.tiers || [];
                if (!tiers.length) {
                    html += '<div class="text-center text-muted py-4">پله‌ای در انتظار ثبت نیست.</div>';
                    bodyEl.innerHTML = html;
                    return;
                }

                html += '<div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0">' +
                    '<thead class="table-light"><tr><th>پله</th><th>کاربر / سفارش</th><th>تارگت</th><th>قیمت فروش</th>' +
                    '<th>مقدار</th><th>تا قابل ثبت شدن</th><th>در انتظار از / تلاش‌ها</th><th>عملیات</th></tr></thead><tbody>';
                tiers.forEach(function(r) {
                    var target = r.target_type === 'percent' ?
                        fmt(r.target_value, 4) + '٪ <small class="text-muted">(خرید ' + fmt(r.avg_buy_price) + ')</small>' :
                        fmt(r.target_value);
                    var distance = r.placeable_now ?
                        '<span class="badge bg-label-success">قابل ثبت</span>' :
                        (r.rise_needed_percent !== null ?
                            '<small>میانگین قیمت باید ' + pct(r.rise_needed_percent) + ' بالا برود</small>' :
                            '<small class="text-muted">نامشخص</small>');
                    html += '<tr class="' + (r.placeable_now ? 'table-success' : '') + '">' +
                        '<td class="font-number">#' + r.sell_order_id + '<div><small class="text-muted">' + fmt(r.share_percent, 2) + '٪</small></div></td>' +
                        '<td><small>' + esc(r.user_label || '—') + '</small><div><small class="text-muted font-number">سفارش #' +
                        r.bot_order_id + '</small></div></td>' +
                        '<td class="font-number">' + target + '</td>' +
                        '<td class="font-number">' + fmt(r.target_price) + '<div><small class="text-muted">' + pct(r.above_live_percent) +
                        ' نسبت به بازار</small></div></td>' +
                        '<td class="font-number">' + fmt(r.amount) + '</td>' +
                        '<td>' + distance + '</td>' +
                        '<td><small>' + when(r.waiting_since) + '</small><div><small class="text-muted">' + r.placement_attempts +
                        ' تلاش</small>' + (r.last_placement_error ?
                            ' <i class="fas fa-exclamation-circle text-danger" title="' + esc(r.last_placement_error).replace(/"/g, '&quot;') + '"></i>' :
                            '') + '</div></td>' +
                        '<td class="text-nowrap"><button type="button" class="btn btn-sm btn-outline-primary js-place-tier" data-id="' +
                        r.sell_order_id + '"' + (d.block_reason ? ' disabled' : '') + '>ثبت این پله</button></td>' +
                        '</tr>';
                });
                html += '</tbody></table></div>';
                bodyEl.innerHTML = html;

                bodyEl.querySelectorAll('.js-place-tier').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        if (!confirm('پله #' + btn.dataset.id + ' همین الان در Binance ثبت شود؟ اگر قیمتش بالاتر از سقف باشد Binance آن را رد می‌کند و پله در انتظار می‌ماند.')) return;
                        busy(btn, true);
                        request('POST', placeTierUrl.replace('__ID__', btn.dataset.id)).then(function(res) {
                            toast(res.ok ? res.message : (res.error || 'ثبت انجام نشد.'), !!res.ok);
                            if (res.ok) {
                                changed = true;
                                load();
                            }
                        }).catch(function() {
                            toast('خطا در ارتباط با سرور.', false);
                        }).finally(function() {
                            busy(btn, false);
                        });
                    });
                });
            }

            function load() {
                bodyEl.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
                request('GET', active.detailsUrl).then(function(d) {
                    if (!d.ok) {
                        bodyEl.innerHTML = '<div class="alert alert-danger mb-0">' + esc(d.error || 'خطا در دریافت جزئیات.') + '</div>';
                        return;
                    }
                    renderDetails(d);
                }).catch(function() {
                    bodyEl.innerHTML = '<div class="alert alert-danger mb-0">خطا در ارتباط با سرور.</div>';
                });
            }

            document.querySelectorAll('.js-waiting-details').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    active = {
                        detailsUrl: btn.dataset.detailsUrl,
                        placeUrl: btn.dataset.placeUrl,
                        symbol: btn.dataset.symbol
                    };
                    titleEl.textContent = 'پله‌های در انتظار ثبت ' + btn.dataset.symbol;
                    modal.show();
                    load();
                });
            });

            placeAllBtn.addEventListener('click', function() {
                if (active) placeCurrency(active.placeUrl, active.symbol, placeAllBtn);
            });

            modalEl.addEventListener('hidden.bs.modal', function() {
                if (changed) window.location.reload();
            });

            /* ── Filters ── */
            var searchEl = document.getElementById('currencySearch');
            var stateEl = document.getElementById('stateFilter');
            var rows = Array.prototype.slice.call(document.querySelectorAll('#waitingTable tbody tr[data-search]'));
            var noResult = document.getElementById('noFilterResult');
            var summary = document.getElementById('activeFilters');
            var summaryList = document.getElementById('activeFiltersList');
            var resultCount = document.getElementById('filterResultCount');

            function applyFilters() {
                var q = searchEl.value.trim().toLowerCase();
                var state = stateEl.value;
                var visible = 0;

                rows.forEach(function(tr) {
                    var match = (!q || tr.dataset.search.indexOf(q) !== -1) && (!state || tr.dataset.state === state);
                    tr.classList.toggle('d-none', !match);
                    if (match) visible++;
                });
                noResult.classList.toggle('d-none', visible > 0 || rows.length === 0);

                var badges = [];
                if (q) badges.push('ارز: ' + searchEl.value.trim());
                if (state) badges.push('وضعیت: ' + stateEl.options[stateEl.selectedIndex].text);
                summaryList.innerHTML = badges.map(function(b) {
                    var span = document.createElement('span');
                    span.className = 'badge bg-primary';
                    span.textContent = b;
                    return span.outerHTML;
                }).join('');
                resultCount.textContent = visible + ' ارز';
                summary.classList.toggle('d-none', badges.length === 0);
                summary.classList.toggle('d-flex', badges.length > 0);
            }

            searchEl.addEventListener('input', applyFilters);
            stateEl.addEventListener('change', applyFilters);
            document.getElementById('clearFilters').addEventListener('click', function() {
                searchEl.value = '';
                stateEl.value = '';
                applyFilters();
            });
        });
    </script>
@endpush
