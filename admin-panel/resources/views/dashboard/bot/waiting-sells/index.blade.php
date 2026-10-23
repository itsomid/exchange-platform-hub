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
            <p class="text-muted mb-0" style="max-width: 60rem; line-height: 1.8;">
                پله‌ها به دو دلیل ممکن است هنوز در Binance ثبت نشده باشند:
                <strong>سقف قیمت</strong> — Binance فروش Limit را فقط تا چند برابر میانگین قیمت اخیر قبول می‌کند
                (PERCENT_PRICE_BY_SIDE)؛
                <strong>سقف تعداد سفارش</strong> — هر حساب روی هر بازار حداکثر تعداد مشخصی سفارش باز دارد (MAX_NUM_ORDERS،
                معمولاً ۲۰۰).
                سیستم هر دقیقه بررسی می‌کند: پله‌ای که زیر سقف قیمت آمد و جای خالی داشت ثبت می‌شود؛ وقتی جا پر است،
                دورترین سفارش ربات که هیچ بخشی از آن پر نشده لغو و به صف برمی‌گردد تا پله نزدیک‌تر ثبت شود. اگر قیمت از
                تارگت پله‌ای بگذرد، با Market فروخته و مثل پله پرشده تسویه می‌شود.
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
                        <span class="d-block text-muted mb-1">کل پله‌های در انتظار ثبت</span>
                        <h4 class="mb-0 font-number">{{ $rows->sum('tiers') }}</h4>
                        <small class="text-muted font-number">{{ $rows->count() }} ارز · {{ $rows->sum('users') }} کاربر ·
                            {{ $rows->sum('orders') }} سفارش</small>
                    </div>
                    <span class="badge bg-label-primary rounded p-2"><i class="fa-light fa-hourglass-half fa-lg"></i></span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-start justify-content-between">
                    <div>
                        <span class="d-block text-muted mb-1">به‌خاطر سقف قیمت</span>
                        <h4 class="mb-0 font-number text-warning">{{ $rows->sum('price_tiers') }}</h4>
                        <small class="text-muted font-number">{{ $rows->where('price_tiers', '>', 0)->count() }} ارز ·
                            منتظر بالا رفتن میانگین قیمت</small>
                    </div>
                    <span class="badge bg-label-warning rounded p-2"><i class="fa-light fa-arrow-up-to-line fa-lg"></i></span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-start justify-content-between">
                    <div>
                        <span class="d-block text-muted mb-1">به‌خاطر سقف تعداد سفارش</span>
                        <h4 class="mb-0 font-number text-info">{{ $rows->sum('slot_tiers') }}</h4>
                        <small class="text-muted font-number">{{ $rows->where('slot_tiers', '>', 0)->count() }} ارز ·
                            منتظر خالی شدن جا در Binance</small>
                    </div>
                    <span class="badge bg-label-info rounded p-2"><i class="fa-light fa-layer-group fa-lg"></i></span>
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
                            <small class="text-muted font-number d-block">
                                @if (!empty($lastRun['disabled']))
                                    ثبت خودکار خاموش بود
                                @elseif ($totals)
                                    ثبت {{ $totals['placed'] }} · Market {{ $totals['completed'] }} · جابه‌جایی {{ $totals['rotated'] ?? 0 }} · رد {{ $totals['rejected'] }}
                                @endif
                            </small>
                        @else
                            <h6 class="mb-0 text-muted">—</h6>
                            <small class="text-muted d-block">اطلاعاتی از api-service دریافت نشد</small>
                        @endif
                        <small>ثبت خودکار: <span class="fw-semibold {{ $autoPlace ? 'text-success' : 'text-danger' }}"
                                id="autoPlaceState">{{ $autoPlace ? 'فعال' : 'غیرفعال' }}</span></small>
                    </div>
                    <span class="badge bg-label-success rounded p-2"><i
                            class="fa-light fa-clock-rotate-left fa-lg"></i></span>
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
                    <label class="form-label" for="reasonFilter">دلیل انتظار</label>
                    <select id="reasonFilter" class="form-select">
                        <option value="">همه دلایل</option>
                        <option value="price">سقف قیمت</option>
                        <option value="slot">سقف تعداد سفارش</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label" for="stateFilter">وضعیت</label>
                    <select id="stateFilter" class="form-select">
                        <option value="">همه ارزها</option>
                        <option value="error">دارای خطای ثبت</option>
                        <option value="clean">بدون خطا</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 d-flex align-items-end justify-content-lg-end gap-2">
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
                            <th class="text-nowrap">به تفکیک دلیل</th>
                            <th class="text-nowrap">سفارش‌های باز Binance</th>
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
                                data-state="{{ $row->last_error ? 'error' : 'clean' }}"
                                data-reasons="{{ trim(($row->price_tiers ? 'price ' : '') . ($row->slot_tiers ? 'slot' : '')) }}">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar avatar-sm">
                                            <img src="{{ $row->currency->coinLogo() }}" alt=""
                                                class="rounded-circle" width="32" height="32">
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
                                    <div><small>{{ formatNumberTrimZeros($row->amount, 8) }} {{ $row->symbol }}</small>
                                    </div>
                                </td>
                                <td class="font-number text-nowrap">
                                    <div class="{{ $row->price_tiers ? '' : 'text-muted opacity-50' }}">
                                        <span class="badge bg-label-warning" title="قیمت پله بالاتر از سقف قیمت Binance است">سقف قیمت</span>
                                        <strong>{{ $row->price_tiers }}</strong>
                                        <small class="text-muted">· {{ formatNumberTrimZeros($row->price_amount, 8) }}</small>
                                    </div>
                                    <div class="mt-1 {{ $row->slot_tiers ? '' : 'text-muted opacity-50' }}">
                                        <span class="badge bg-label-info" title="سقف تعداد سفارش باز این بازار در Binance پر است">سقف تعداد</span>
                                        <strong>{{ $row->slot_tiers }}</strong>
                                        <small class="text-muted">· {{ formatNumberTrimZeros($row->slot_amount, 8) }}</small>
                                    </div>
                                </td>
                                <td class="font-number text-nowrap">
                                    @if ($row->slots)
                                        @php
                                            $usable = max(1, $row->slots['max'] - $row->slots['reserve']);
                                            $usedPct = min(100, round($row->slots['open'] / $usable * 100));
                                        @endphp
                                        <strong>{{ $row->slots['open'] }}</strong><small class="text-muted"> از {{ $row->slots['max'] }}</small>
                                        <div class="progress my-1" style="height: 4px; width: 7rem;">
                                            <div class="progress-bar {{ $row->slots['free'] > 0 ? 'bg-success' : 'bg-danger' }}"
                                                style="width: {{ $usedPct }}%"></div>
                                        </div>
                                        <small class="{{ $row->slots['free'] > 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $row->slots['free'] }} جای خالی</small>
                                        <small class="text-muted">· {{ $row->slots['reserve'] }} رزرو</small>
                                    @else
                                        <span class="text-muted" title="در آخرین اجرای خودکار دریافت نشد">—</span>
                                    @endif
                                    <div><small class="text-muted">{{ $row->placed_tiers }} سفارش ربات</small></div>
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
                                        <div><small class="text-muted"
                                                dir="ltr">+{{ number_format($row->lowest_above, 2) }}٪</small>
                                            <small class="text-muted">از بازار</small>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $row->oldest ? \App\Helpers\DateFormatter::convertToPersianDate($row->oldest, '%Y/%m/%d H:i') : '—' }}</small>
                                </td>
                                <td>
                                    <small>{{ $row->last_check ? \App\Helpers\DateFormatter::convertToPersianDate($row->last_check, '%Y/%m/%d H:i') : '—' }}</small>
                                    @if ($row->last_error)
                                        <i class="fas fa-exclamation-circle text-danger ms-1"
                                            title="{{ $row->last_error }}"></i>
                                    @endif
                                    <div><small class="text-muted">{{ $row->attempts }} تلاش</small></div>
                                </td>
                                <td class="pe-4 text-center">
                                    <div class="waiting-row-actions">
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
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-5">
                                    <i class="fa-light fa-circle-check fa-2x d-block mb-2 opacity-50"></i>
                                    هیچ پله فروشی در انتظار ثبت نیست.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="noFilterResult" class="d-none">
                            <td colspan="10" class="text-center text-muted py-4">ارزی با این مشخصات پیدا نشد.</td>
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
    <style>
        .waiting-row-actions {
            display: grid;
            gap: .35rem;
            width: 11.75rem;
            margin-inline: auto;
        }

        .waiting-row-actions .btn {
            width: 100%;
        }

        /* Both scrollbars on modal-body so the horizontal bar stays visible like the vertical one. */
        #waitingDetailsModal .modal-dialog {
            max-width: min(1140px, calc(100vw - 2rem));
        }

        #waitingDetailsModal .modal-content,
        #waitingDetailsModal .modal-body {
            min-width: 0;
        }

        #waitingDetailsModal .modal-body {
            overflow-x: auto !important;
            overflow-y: auto !important;
            scrollbar-width: thin;
            scrollbar-color: #696cff #eceef1;
        }

        #waitingDetailsModal .modal-body::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        #waitingDetailsModal .modal-body::-webkit-scrollbar-track {
            background: #eceef1;
            border-radius: 8px;
        }

        #waitingDetailsModal .modal-body::-webkit-scrollbar-thumb {
            background: #696cff;
            border-radius: 8px;
        }

        .waiting-tiers-scroll {
            width: max-content;
            min-width: 100%;
        }

        .waiting-tiers-table {
            width: max-content;
            min-width: 100%;
            font-size: .8125rem;
        }

        .waiting-tiers-table > :not(caption) > * > * {
            padding: .3rem .5rem;
            white-space: nowrap;
            vertical-align: middle;
        }

        .waiting-tiers-table .col-forecast {
            white-space: normal;
            min-width: 13rem;
            max-width: 16rem;
            line-height: 1.45;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var csrf = '{{ csrf_token() }}';
            var placeTierUrl = @json(route('admin.bot.waiting-sells.place-tier', ['sellOrder' => '__ID__']));
            var NEAR_RISE_PERCENT = 10;

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
                    toggleState.className = 'fw-semibold ' + (d.enabled ? 'text-success' : 'text-danger');
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
                if (!confirm('پله‌های در انتظار ' + symbol +
                        ' بررسی و هر کدام که زیر سقف قیمت Binance است و جا دارد (یا با جابه‌جایی جا پیدا می‌کند) ثبت شود؟'
                    )) return;
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
                    html += '<div class="alert alert-warning"><i class="fas fa-ban me-1"></i>' + esc(d
                        .block_reason) + '</div>';
                }

                var groups = d.groups || {};
                var gp = groups.price_ceiling || {
                    count: 0
                };
                var gs = groups.order_limit || {
                    count: 0
                };
                var s = d.slots;

                function forecastLine(grp, slotGroup) {
                    if (!grp.count) {
                        return '<div class="small text-muted mt-2">پله‌ای به این دلیل در انتظار نیست.</div>';
                    }
                    var parts = [];
                    if (grp.placeable_now) {
                        parts.push('<span class="font-number">' + grp.placeable_now +
                            '</span> پله در اجرای بعدی (حداکثر یک دقیقه دیگر) ثبت یا با Market فروخته می‌شود');
                    }
                    if (grp.first_price !== null && grp.first_price !== undefined) {
                        parts.push('بقیه از وقتی ' + (slotGroup ? 'قیمت' : 'میانگین قیمت') +
                            ' به <strong class="font-number">' + fmt(grp.first_price) + '</strong> (' + pct(grp
                                .first_rise) + ') برسد ثبت می‌شوند و آخرین پله با <strong class="font-number">' +
                            fmt(grp.last_price) + '</strong> (' + pct(grp.last_rise) + ')');
                    }
                    var out = '<hr class="my-2"><div class="small"><i class="fas fa-chart-line me-1 text-primary"></i>' +
                        '<strong>پیش‌بینی:</strong> ' + parts.join('؛ ') + '.';
                    if (slotGroup && grp.max_lower_to_sell) {
                        out += '<div class="text-muted mt-1">برای جا باز شدن برای آخرین پله، <span class="font-number">' +
                            grp.max_lower_to_sell +
                            '</span> پله پایین‌تر (سفارش‌های فعلی ربات و پله‌های جلوتر در صف) باید فروش بروند.</div>';
                    }
                    if (grp.by_ceiling && grp.by_slot) {
                        out += '<div class="text-muted mt-1">از این گروه، <span class="font-number">' + grp.by_ceiling +
                            '</span> پله را در عمل سقف قیمت و <span class="font-number">' + grp.by_slot +
                            '</span> پله را سقف تعداد سفارش عقب نگه داشته است.</div>';
                    }
                    return out + '</div>';
                }

                function groupHeader(badgeCls, badge, caption, grp) {
                    return '<div class="d-flex flex-wrap justify-content-between align-items-center gap-1 mb-2">' +
                        '<h6 class="mb-0"><span class="badge ' + badgeCls + ' me-1">' + badge + '</span>' + caption +
                        '</h6><span class="font-number fw-semibold">' + (grp.count || 0) + ' پله · ' + fmt(grp
                            .amount || 0) + ' ' + sym + '</span></div>';
                }

                html += '<div class="small text-muted mb-2">قیمت لحظه‌ای ' + sym + ': <span class="font-number">' +
                    fmt(d.live_price) + '</span> USDT</div>';
                html += '<div class="row g-3 mb-3">';

                html += '<div class="col-lg-6"><div class="border border-warning rounded h-100 p-3">' +
                    groupHeader('bg-label-warning', 'سقف قیمت', 'منتظر بالا رفتن میانگین قیمت', gp) +
                    '<div class="small" style="line-height: 1.9;">';
                if (c) {
                    html += 'Binance فروش Limit را تا <span class="font-number">' + fmt(c.multiplier_up, 4) +
                        '</span> برابر میانگین قیمت ' +
                        (c.avg_price_mins ? '<span class="font-number">' + c.avg_price_mins + '</span> دقیقه اخیر' :
                            'اخیر') +
                        ' قبول می‌کند (' + pct(c.max_percent_above) + ' بالاتر از بازار). ' +
                        (c.reference === 'avg' ? 'میانگین فعلی' : 'قیمت لحظه‌ای (میانگین Binance دریافت نشد)') +
                        ' <span class="font-number">' + fmt(c.reference_price) +
                        '</span> USDT؛ سقف الان <strong class="font-number">' + fmt(c.max_price) +
                        '</strong> USDT و با حاشیه اطمینان، پله‌ها تا <strong class="font-number">' +
                        fmt(c.place_max_price) + '</strong> USDT ثبت می‌شوند.';
                } else {
                    html += 'سقف قیمت Binance برای این ارز دریافت نشد؛ فقط پله‌هایی که قیمت از تارگتشان گذشته ' +
                        '(فروش Market) قابل اقدام‌اند.';
                }
                html += '</div>' + forecastLine(gp, false) + '</div></div>';

                html += '<div class="col-lg-6"><div class="border border-info rounded h-100 p-3">' +
                    groupHeader('bg-label-info', 'سقف تعداد سفارش', 'منتظر خالی شدن جا در Binance', gs) +
                    '<div class="small" style="line-height: 1.9;">';
                if (s) {
                    var usable = Math.max(1, s.max - s.reserve);
                    html += 'Binance روی هر بازار برای کل حساب حداکثر <strong class="font-number">' + s.max +
                        '</strong> سفارش باز قبول می‌کند. الان <strong class="font-number">' + s.open +
                        '</strong> سفارش باز است: <span class="font-number">' + s.bot_placed +
                        '</span> سفارش ربات و <span class="font-number">' + s.others +
                        '</span> سفارش دیگر حساب. <span class="font-number">' + s.reserve +
                        '</span> جا برای بقیه حساب رزرو است، پس ربات حداکثر <strong class="font-number">' + s
                        .bot_capacity + '</strong> سفارش روی این بازار نگه می‌دارد. جای خالی الان: ' +
                        '<strong class="font-number ' + (s.free > 0 ? 'text-success' : 'text-danger') + '">' + s.free +
                        '</strong>.' +
                        '<div class="progress my-2" style="height: 6px;"><div class="progress-bar ' + (s.free > 0 ?
                            'bg-success' : 'bg-danger') + '" style="width: ' + Math.min(100, Math.round(s.open /
                            usable * 100)) + '%"></div></div>';
                    if (d.rotation && d.rotation.enabled) {
                        html += '<div class="text-muted">وقتی جا پر است، دورترین سفارش ربات (اگر هیچ بخشی از آن پر ' +
                            'نشده و تارگتش دست‌کم <span class="font-number">' + fmt(d.rotation.min_gap_percent, 2) +
                            '٪</span> بالاتر باشد) لغو و به صف برمی‌گردد تا پله نزدیک‌تر جایش ثبت شود.</div>';
                    }
                } else {
                    html += 'سفارش‌های باز Binance برای این ارز دریافت نشد؛ پیش‌بینی بر اساس ظرفیت ممکن نیست.';
                }
                html += '</div>' + forecastLine(gs, true) + '</div></div>';
                html += '</div>';

                var tiers = d.tiers || [];
                if (!tiers.length) {
                    html += '<div class="text-center text-muted py-4">پله‌ای در انتظار ثبت نیست.</div>';
                    bodyEl.innerHTML = html;
                    return;
                }

                var soon = 0;
                tiers.forEach(function(r) {
                    if (r.binding === 'now' || r.binding === 'hit') soon++;
                });

                function stat(label, value, cls) {
                    return '<div class="col"><div class="border rounded p-2 h-100 text-center">' +
                        '<div class="small text-muted mb-1">' + label + '</div>' +
                        '<h5 class="mb-0 font-number ' + cls + '">' + value + '</h5></div></div>';
                }

                html += '<div class="row row-cols-2 row-cols-md-4 g-2 mb-3">' +
                    stat('کل پله‌های در انتظار', tiers.length, '') +
                    stat('ثبت/فروش در اجرای بعدی', soon, 'text-success') +
                    stat('به‌خاطر سقف قیمت', gp.count, 'text-warning') +
                    stat('به‌خاطر سقف تعداد سفارش', gs.count, 'text-info') +
                    '</div>';

                var ms = d.milestones || [];
                if (ms.length) {
                    html += '<h6 class="mb-2"><i class="fas fa-chart-line me-1 text-primary"></i>پیش‌بینی ثبت با بالا رفتن قیمت</h6>' +
                        '<div class="table-responsive mb-2"><table class="table table-sm table-bordered text-center align-middle mb-0">' +
                        '<thead class="table-light"><tr><th>اگر قیمت برسد به</th><th>ثبت‌شده از «سقف قیمت»</th>' +
                        '<th>ثبت‌شده از «سقف تعداد سفارش»</th><th>جمع ثبت‌شده</th><th>هنوز در انتظار</th></tr></thead><tbody>';
                    ms.forEach(function(m) {
                        html += '<tr><td class="font-number">' + fmt(m.price) + ' <small class="text-muted">(' + (m
                                .rise_percent ? pct(m.rise_percent) : 'قیمت فعلی') + ')</small></td>' +
                            '<td class="font-number">' + m.price_ceiling + ' <small class="text-muted">از ' + gp.count +
                            '</small></td>' +
                            '<td class="font-number">' + m.order_limit + ' <small class="text-muted">از ' + gs.count +
                            '</small></td>' +
                            '<td class="font-number fw-semibold">' + m.total + '</td>' +
                            '<td class="font-number ' + (m.remaining ? '' : 'text-success') + '">' + m.remaining +
                            '</td></tr>';
                    });
                    html += '</tbody></table></div>' +
                        '<div class="small text-muted mb-3" style="line-height: 1.8;">فرض پیش‌بینی: سفارش جدیدی اضافه نشود ' +
                        'و پله‌های پایین‌تر با رسیدن قیمت به تارگتشان پر شوند. سقف قیمت با میانگین قیمت Binance (چند دقیقه اخیر) ' +
                        'سنجیده می‌شود، پس بعد از جهش قیمت چند دقیقه طول می‌کشد تا پله‌ها ثبت شوند. زمان دقیق به حرکت بازار بستگی دارد.</div>';
                }

                function reasonBtn(reason, label, count) {
                    return '<button type="button" class="btn btn-outline-primary' + (reason ? '' : ' active') +
                        '" data-reason="' + reason + '">' + label + ' <span class="font-number">(' + count +
                        ')</span></button>';
                }

                html += '<div class="d-flex flex-wrap align-items-center gap-2 mb-2"><span class="small text-muted">نمایش پله‌ها:</span>' +
                    '<div class="btn-group btn-group-sm" role="group" id="tierReasonFilter">' +
                    reasonBtn('', 'همه', tiers.length) +
                    reasonBtn('price_ceiling', 'سقف قیمت', gp.count) +
                    reasonBtn('order_limit', 'سقف تعداد سفارش', gs.count) +
                    '</div></div>';

                html +=
                    '<div class="waiting-tiers-scroll"><table class="table table-sm table-bordered align-middle mb-0 waiting-tiers-table">' +
                    '<thead class="table-light"><tr><th>پله</th><th>کاربر / سفارش</th><th>تارگت</th><th>قیمت فروش</th>' +
                    '<th>مقدار</th><th>دلیل انتظار</th><th>پیش‌بینی ثبت</th><th>در انتظار از / تلاش‌ها</th><th>عملیات</th></tr></thead><tbody>';
                tiers.forEach(function(r) {
                    var target = r.target_type === 'percent' ?
                        fmt(r.target_value, 4) + '٪ <small class="text-muted">(خرید ' + fmt(r
                        .avg_buy_price) + ')</small>' :
                        fmt(r.target_value);
                    var soonTier = r.binding === 'now' || r.binding === 'hit';
                    var isNear = !soonTier && r.forecast_rise_percent !== null &&
                        parseFloat(r.forecast_rise_percent) <= NEAR_RISE_PERCENT;
                    var reason = r.waiting_reason === 'order_limit' ?
                        '<span class="badge bg-label-info">سقف تعداد سفارش</span>' :
                        '<span class="badge bg-label-warning">سقف قیمت</span>';
                    var forecast;
                    if (r.binding === 'hit') {
                        forecast = '<span class="badge bg-label-success">قیمت از تارگت گذشته</span>' +
                            '<div><small class="text-muted">در اجرای بعدی با Market فروخته می‌شود</small></div>';
                    } else if (r.binding === 'now') {
                        forecast = '<span class="badge bg-label-success">قابل ثبت</span><div><small class="text-muted">' +
                            (r.waiting_reason === 'order_limit' ? 'در اجرای بعدی، با جای خالی یا جابه‌جایی' :
                                'در اجرای بعدی') + '</small></div>';
                    } else if (r.binding === 'ceiling') {
                        forecast = '<strong class="font-number">' + fmt(r.forecast_price) + '</strong> ' + pct(r
                                .forecast_rise_percent) +
                            '<div><small class="text-muted">وقتی میانگین قیمت به این عدد برسد (سقف قیمت)</small></div>' +
                            (r.slot_price ? '<div><small class="text-muted">جای خالی از قیمت <span class="font-number">' +
                                fmt(r.slot_price) + '</span> فراهم است</small></div>' : '');
                    } else if (r.binding === 'unknown') {
                        forecast = '<small class="text-muted">نامشخص؛ سقف قیمت Binance دریافت نشد</small>';
                    } else {
                        forecast = '<strong class="font-number">' + fmt(r.forecast_price) + '</strong> ' + pct(r
                                .forecast_rise_percent) +
                            '<div><small class="text-muted">وقتی <span class="font-number">' + r.lower_to_sell +
                            '</span> پله پایین‌تر فروش برود و جا خالی شود</small></div>';
                    }
                    html += '<tr data-reason="' + esc(r.waiting_reason) + '" class="' + (soonTier ? 'table-success' : (
                            isNear ? 'table-warning' : '')) +
                        '">' +
                        '<td class="font-number">#' + r.sell_order_id + '<div><small class="text-muted">' +
                        fmt(r.share_percent, 2) + '٪</small></div></td>' +
                        '<td><small>' + esc(r.user_label || '—') +
                        '</small><div><small class="text-muted font-number">سفارش #' +
                        r.bot_order_id + '</small></div></td>' +
                        '<td class="font-number">' + target + '</td>' +
                        '<td class="font-number">' + fmt(r.target_price) +
                        '<div><small class="text-muted">' + pct(r.above_live_percent) +
                        ' نسبت به بازار</small></div></td>' +
                        '<td class="font-number">' + fmt(r.amount) + '</td>' +
                        '<td>' + reason + '</td>' +
                        '<td class="col-forecast">' + forecast + '</td>' +
                        '<td><small>' + when(r.waiting_since) + '</small><div><small class="text-muted">' +
                        r.placement_attempts +
                        ' تلاش</small>' + (r.last_placement_error ?
                            ' <i class="fas fa-exclamation-circle text-danger" title="' + esc(r
                                .last_placement_error).replace(/"/g, '&quot;') + '"></i>' :
                            '') + '</div></td>' +
                        '<td class="text-nowrap"><button type="button" class="btn btn-sm btn-outline-primary js-place-tier" data-id="' +
                        r.sell_order_id + '"' + (d.block_reason ? ' disabled' : '') + '>ثبت</button></td>' +
                        '</tr>';
                });
                html += '</tbody></table></div>';
                html += '<div class="small text-muted mt-2">تعداد سطرها: <span class="font-number">' + tiers
                    .length + '</span> پله</div>';
                bodyEl.innerHTML = html;

                var reasonFilter = bodyEl.querySelector('#tierReasonFilter');
                reasonFilter.querySelectorAll('button').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        reasonFilter.querySelectorAll('button').forEach(function(b) {
                            b.classList.toggle('active', b === btn);
                        });
                        bodyEl.querySelectorAll('tr[data-reason]').forEach(function(tr) {
                            tr.classList.toggle('d-none', !!btn.dataset.reason && tr.dataset.reason !== btn
                                .dataset.reason);
                        });
                    });
                });

                bodyEl.querySelectorAll('.js-place-tier').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        if (!confirm('پله #' + btn.dataset.id +
                                ' همین الان در Binance ثبت شود؟ اگر قیمتش بالاتر از سقف باشد یا جای خالی نباشد، Binance آن را رد می‌کند و پله در انتظار می‌ماند.'
                                )) return;
                        busy(btn, true);
                        request('POST', placeTierUrl.replace('__ID__', btn.dataset.id)).then(
                            function(res) {
                                toast(res.ok ? res.message : (res.error || 'ثبت انجام نشد.'), !!
                                    res.ok);
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
                bodyEl.innerHTML =
                    '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
                request('GET', active.detailsUrl).then(function(d) {
                    if (!d.ok) {
                        bodyEl.innerHTML = '<div class="alert alert-danger mb-0">' + esc(d.error ||
                            'خطا در دریافت جزئیات.') + '</div>';
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
            var reasonEl = document.getElementById('reasonFilter');
            var rows = Array.prototype.slice.call(document.querySelectorAll('#waitingTable tbody tr[data-search]'));
            var noResult = document.getElementById('noFilterResult');
            var summary = document.getElementById('activeFilters');
            var summaryList = document.getElementById('activeFiltersList');
            var resultCount = document.getElementById('filterResultCount');

            function applyFilters() {
                var q = searchEl.value.trim().toLowerCase();
                var state = stateEl.value;
                var reason = reasonEl.value;
                var visible = 0;

                rows.forEach(function(tr) {
                    var match = (!q || tr.dataset.search.indexOf(q) !== -1) && (!state || tr.dataset
                        .state === state) && (!reason || tr.dataset.reasons.split(' ').indexOf(reason) !== -1);
                    tr.classList.toggle('d-none', !match);
                    if (match) visible++;
                });
                noResult.classList.toggle('d-none', visible > 0 || rows.length === 0);

                var badges = [];
                if (q) badges.push('ارز: ' + searchEl.value.trim());
                if (reason) badges.push('دلیل: ' + reasonEl.options[reasonEl.selectedIndex].text);
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
            reasonEl.addEventListener('change', applyFilters);
            document.getElementById('clearFilters').addEventListener('click', function() {
                searchEl.value = '';
                stateEl.value = '';
                reasonEl.value = '';
                applyFilters();
            });
        });
    </script>
@endpush
