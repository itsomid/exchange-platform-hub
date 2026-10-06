@extends('dashboard.layout.master')

@section('title', 'لغو بر اساس ارز — ربات معاملاتی')

@section('content')
    @php
        $openRows = $rows->where('open_tiers', '>', 0);
        $sumOpenCost = $openRows->sum('open_cost');
        $sumOpenValue = $openRows->sum(fn($r) => $r->open_value ?? 0);
        $sumOpenUsers = $openRows->sum('open_users');
    @endphp

    {{-- Page header --}}
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-4">
        <div>
            <h4 class="mb-1">ارزهای سبد کاربران ربات</h4>
            <p class="text-muted mb-0" style="max-width: 52rem; line-height: 1.8;">
                هر ارزی که در سفارش‌های ربات خریداری شده، با موقعیت باز فعلی آن در همه سفارش‌ها.
                با دکمه لغو، همه پله‌های فروش باز همان ارز در سفارش‌های همه کاربران لغو و موجودی به کیف پول ربات کاربر
                برمی‌گردد.
            </p>
        </div>
        <span class="badge bg-label-primary rounded-pill px-3 py-2 align-self-center">
            <i class="fas fa-coins me-1"></i>
            {{ $rows->count() }} ارز
        </span>
    </div>

    {{-- Summary cards --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="d-block text-muted mb-1">ارزهای دارای موقعیت باز</span>
                            <div class="d-flex align-items-baseline gap-1 my-1">
                                <h4 class="mb-0 font-number">{{ $openRows->count() }}</h4>
                                <small class="text-muted">از {{ $rows->count() }}</small>
                            </div>
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="fa-light fa-coins fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="d-block text-muted mb-1">اصل سرمایه قفل در موقعیت‌های باز</span>
                            <div class="d-flex align-items-baseline gap-1 my-1">
                                <h4 class="mb-0 font-number">{{ formatNumberTrimZeros($sumOpenCost, 2) }}</h4>
                                <small class="text-muted">USDT</small>
                            </div>
                        </div>
                        <span class="badge bg-label-warning rounded p-2">
                            <i class="fa-light fa-lock fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="d-block text-muted mb-1">ارزش لحظه‌ای موقعیت‌های باز</span>
                            <div class="d-flex align-items-baseline gap-1 my-1">
                                <h4 class="mb-0 font-number">{{ formatNumberTrimZeros($sumOpenValue, 2) }}</h4>
                                <small class="text-muted">USDT</small>
                            </div>
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                            <i class="fa-light fa-chart-line fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="d-block text-muted mb-1">موقعیت‌های باز کاربران</span>
                            <div class="d-flex align-items-baseline gap-1 my-1">
                                <h4 class="mb-0 font-number">{{ $sumOpenUsers }}</h4>
                            </div>
                            <small class="text-muted">کاربر × ارز</small>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
                            <i class="fa-light fa-users fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main table card --}}
    <div class="card mb-4">
        <div class="card-header border-bottom">
            <div class="row g-3 align-items-end">
                <div class="col-lg-4 col-md-6">
                    <label class="form-label" for="currencySearch">جستجوی ارز</label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="currencySearch" class="form-control" autocomplete="off"
                            placeholder="نماد یا نام ارز، مثلاً BTC">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label" for="positionFilter">وضعیت موقعیت</label>
                    <select id="positionFilter" class="form-select">
                        <option value="">همه ارزها</option>
                        <option value="open">فقط دارای موقعیت باز</option>
                        <option value="closed">بدون موقعیت باز</option>
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
                <table class="table table-hover align-middle mb-0" id="currencyTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">ارز</th>
                            <th class="text-nowrap text-center">کاربران<br><small class="text-muted fw-normal">کل /
                                    باز</small></th>
                            <th class="text-nowrap text-center">سفارش‌ها<br><small class="text-muted fw-normal">کل /
                                    باز</small></th>
                            <th class="text-nowrap">تخصیص کل</th>
                            <th class="text-nowrap">مقدار خریده‌شده<br><small class="text-muted fw-normal">میانگین
                                    خرید</small></th>
                            <th class="text-nowrap">قیمت لحظه‌ای</th>
                            <th class="text-nowrap">پله‌های فروش<br><small class="text-muted fw-normal">باز · پر ·
                                    لغو</small></th>
                            <th class="text-nowrap">موقعیت باز<br><small class="text-muted fw-normal">مقدار / اصل
                                    سرمایه</small></th>
                            <th class="text-nowrap">ارزش لحظه‌ای باز<br><small class="text-muted fw-normal">سود/زیان
                                    باز</small></th>
                            <th class="text-nowrap">سود محقق‌شده<br><small class="text-muted fw-normal">کارمزد
                                    عملکرد</small></th>
                            <th class="pe-4 text-center">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php
                                $hasOpen = $row->open_tiers > 0;
                                $rowClass = $hasOpen ? '' : 'table-secondary opacity-75';
                            @endphp
                            <tr class="{{ $rowClass }}"
                                data-search="{{ strtolower($row->symbol . ' ' . $row->currency->name . ' ' . $row->currency->persian_name) }}"
                                data-open="{{ $hasOpen ? '1' : '0' }}">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar avatar-sm">
                                            <img src="{{ $row->currency->coinLogo() }}" alt=""
                                                class="rounded-circle" width="32" height="32"
                                                onerror="this.onerror=null;this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2232%22 height=%2232%22><circle cx=%2216%22 cy=%2216%22 r=%2216%22 fill=%22%23e7e7e8%22/><text x=%2250%25%22 y=%2254%25%22 text-anchor=%22middle%22 font-size=%2210%22 fill=%22%23859%22>{{ substr($row->symbol, 0, 2) }}</text></svg>'">
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <strong class="lh-1">{{ $row->symbol }}</strong>
                                                @if ($row->signal_active === true)
                                                    <span class="badge bg-label-success rounded-pill"
                                                        style="font-size:.65rem">سیگنال فعال</span>
                                                @elseif ($row->signal_active === false)
                                                    <span class="badge bg-label-secondary rounded-pill"
                                                        style="font-size:.65rem">سیگنال غیرفعال</span>
                                                @else
                                                    <span class="badge bg-label-secondary rounded-pill"
                                                        style="font-size:.65rem">بدون سیگنال</span>
                                                @endif
                                            </div>
                                            <small
                                                class="text-muted">{{ $row->currency->persian_name ?: $row->currency->name }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="font-number text-center">
                                    <span class="text-muted">{{ $row->users_count }}</span>
                                    <span class="text-muted mx-1">/</span>
                                    <strong
                                        class="{{ $row->open_users > 0 ? 'text-primary' : '' }}">{{ $row->open_users }}</strong>
                                </td>
                                <td class="font-number text-center">
                                    <span class="text-muted">{{ $row->orders_count }}</span>
                                    <span class="text-muted mx-1">/</span>
                                    <strong
                                        class="{{ $row->open_orders > 0 ? 'text-primary' : '' }}">{{ $row->open_orders }}</strong>
                                </td>
                                <td class="font-number" dir="ltr">
                                    {{ formatNumberTrimZeros($row->total_allocated, 2) }}
                                    <small class="text-muted fw-normal">USDT</small>
                                </td>
                                <td class="font-number">
                                    <div>{{ formatNumberTrimZeros($row->total_bought, 8) }}</div>
                                    <small class="text-muted">
                                        avg {{ $row->avg_buy_price ? formatNumberTrimZeros($row->avg_buy_price, 8) : '—' }}
                                    </small>
                                </td>
                                <td class="font-number">
                                    @if ($row->price)
                                        {{ formatNumberTrimZeros($row->price, 8) }}
                                    @else
                                        <span class="text-muted" title="قیمت لحظه‌ای در کش موجود نیست">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge bg-label-primary" title="باز">
                                            باز {{ $row->open_tiers }}
                                        </span>
                                        <span class="badge bg-label-success" title="پر شده">
                                            پر {{ $row->filled_tiers }}
                                        </span>
                                        <span class="badge bg-label-danger" title="لغو شده">
                                            لغو {{ $row->canceled_tiers }}
                                        </span>
                                    </div>
                                </td>
                                <td class="font-number">
                                    @if ($hasOpen)
                                        <div>{{ formatNumberTrimZeros($row->open_amount, 8) }}</div>
                                        <small class="text-muted">{{ formatNumberTrimZeros($row->open_cost, 2) }}
                                            USDT</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="font-number">
                                    @if ($hasOpen && $row->open_value !== null)
                                        <div>{{ formatNumberTrimZeros($row->open_value, 2) }}</div>
                                        <small class="{{ $row->unrealized_pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $row->unrealized_pnl >= 0 ? '+' : '' }}{{ formatNumberTrimZeros($row->unrealized_pnl, 2) }}
                                        </small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="font-number">
                                    <div
                                        class="{{ $row->realized_pnl > 0 ? 'text-success' : ($row->realized_pnl < 0 ? 'text-danger' : '') }}">
                                        {{ formatNumberTrimZeros($row->realized_pnl, 2) }}
                                    </div>
                                    <small class="text-muted">کارمزد
                                        {{ formatNumberTrimZeros($row->performance_fee, 2) }}</small>
                                </td>
                                <td class="pe-4 text-center text-nowrap">
                                    @if ($row->running)
                                        <span class="badge bg-label-warning">
                                            <i class="fas fa-spinner fa-spin me-1"></i>
                                            لغو #{{ $row->running->id }} {{ $row->running->statusLabel() }}
                                        </span>
                                    @elseif ($hasOpen)
                                        <button type="button" class="btn btn-sm btn-danger js-currency-cancel"
                                            data-symbol="{{ $row->symbol }}"
                                            data-preview-url="{{ route('admin.bot.currency.cancel-preview', $row->currency->id) }}"
                                            data-cancel-url="{{ route('admin.bot.currency.cancel', $row->currency->id) }}">
                                            <i class="fas fa-ban me-1"></i> لغو
                                        </button>
                                        @if ($row->in_flight > 0)
                                            <i class="fas fa-hourglass-half text-warning ms-1"
                                                title="{{ $row->in_flight }} خرید این ارز در حال انجام است"></i>
                                        @endif
                                    @else
                                        <span class="badge bg-label-secondary">بسته</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-5">
                                    <i class="fa-light fa-coins fa-2x d-block mb-2 opacity-50"></i>
                                    هنوز هیچ ارزی در سفارش‌های ربات خریداری نشده است.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="noFilterResult" class="d-none">
                            <td colspan="11" class="text-center text-muted py-4">ارزی با این مشخصات پیدا نشد.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- History of coin-cancel runs --}}
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">
                <i class="fa-light fa-clock-rotate-left me-1 text-muted"></i>
                سابقه لغو بر اساس ارز
            </h5>
            <small class="text-muted">۲۰ مورد آخر</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>ارز</th>
                            <th>وضعیت</th>
                            <th>تعیین تکلیف در صرافی مرجع</th>
                            <th>قیمت تسویه</th>
                            <th>کاربران / پله‌ها</th>
                            <th>برگشتی به کیف پول ربات</th>
                            <th>کارمزد عملکرد</th>
                            <th>دلیل</th>
                            <th class="pe-4">ادمین / زمان</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cancellations as $c)
                            @php
                                $statusClass = match ($c->status) {
                                    'DONE' => 'bg-label-success',
                                    'FAILED' => 'bg-label-danger',
                                    default => 'bg-label-warning',
                                };
                            @endphp
                            <tr
                                @if (!$c->isFinished()) data-running-status-url="{{ route('admin.bot.currency.cancellation.status', $c->id) }}" @endif>
                                <td class="ps-4 font-number">{{ $c->id }}</td>
                                <td><strong>{{ strtoupper((string) $c->currency?->symbol) }}</strong></td>
                                <td>
                                    <span class="badge {{ $statusClass }}">{{ $c->statusLabel() }}</span>
                                    @if ($c->error)
                                        <i class="fas fa-exclamation-circle text-danger ms-1"
                                            title="{{ $c->error }}"></i>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $c->exchangeModeLabel() }}</small>
                                    @if ($c->sell_error)
                                        <div><small class="text-danger" title="{{ $c->sell_error }}">
                                                <i class="fas fa-exclamation-triangle me-1"></i>فروش در صرافی مرجع
                                                ناموفق — تسویه با قیمت لحظه‌ای
                                            </small></div>
                                    @endif
                                </td>
                                <td class="font-number">
                                    @if ($c->settlement_price !== null)
                                        {{ formatNumberTrimZeros($c->settlement_price, 8) }}
                                        <div><small
                                                class="text-muted">{{ $c->price_source === 'market_sell' ? 'فروش واقعی' : 'قیمت لحظه‌ای' }}</small>
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="font-number">
                                    {{ $c->users_count }} / {{ $c->sell_orders_count }}
                                    @if ($c->skipped_count > 0)
                                        <div><small class="text-warning"
                                                title="پله‌هایی که در صرافی مرجع پر شده بودند یا لغوشان در صرافی ناموفق بود و باز ماندند">
                                                {{ $c->skipped_count }} پله رد شد</small></div>
                                    @endif
                                </td>
                                <td class="font-number text-success fw-semibold">
                                    {{ formatNumberTrimZeros($c->total_refund, 2) }}
                                </td>
                                <td class="font-number">{{ formatNumberTrimZeros($c->total_performance_fee, 2) }}
                                </td>
                                <td style="max-width:260px"><small class="text-break">{{ $c->reason }}</small>
                                </td>
                                <td class="pe-4">
                                    <small>{{ $c->admin_label ?: '—' }}</small>
                                    <div><small
                                            class="text-muted">{{ \App\Helpers\DateFormatter::convertToPersianDate($c->created_at, '%Y/%m/%d H:i') }}</small>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="fa-light fa-inbox fa-lg d-block mb-2 opacity-50"></i>
                                    هنوز لغوی بر اساس ارز انجام نشده است.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('dashboard.bot.currencies.partials.cancel-modal')
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var searchEl = document.getElementById('currencySearch');
            var positionEl = document.getElementById('positionFilter');
            var rows = Array.prototype.slice.call(document.querySelectorAll(
                '#currencyTable tbody tr[data-search]'));
            var noResult = document.getElementById('noFilterResult');
            var summary = document.getElementById('activeFilters');
            var summaryList = document.getElementById('activeFiltersList');
            var resultCount = document.getElementById('filterResultCount');

            function applyFilters() {
                var q = searchEl.value.trim().toLowerCase();
                var pos = positionEl.value;
                var visible = 0;

                rows.forEach(function(tr) {
                    var match = (!q || tr.dataset.search.indexOf(q) !== -1) &&
                        (!pos || (pos === 'open') === (tr.dataset.open === '1'));
                    tr.classList.toggle('d-none', !match);
                    if (match) visible++;
                });
                noResult.classList.toggle('d-none', visible > 0 || rows.length === 0);

                var badges = [];
                if (q) badges.push('ارز: ' + searchEl.value.trim());
                if (pos) badges.push('وضعیت: ' + positionEl.options[positionEl.selectedIndex].text);
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
            positionEl.addEventListener('change', applyFilters);
            document.getElementById('clearFilters').addEventListener('click', function() {
                searchEl.value = '';
                positionEl.value = '';
                applyFilters();
            });

            // A run started earlier is still queued/running: refresh once it ends.
            var running = document.querySelectorAll('[data-running-status-url]');
            if (running.length) {
                var timer = setInterval(function() {
                    Promise.all(Array.prototype.map.call(running, function(tr) {
                        return fetch(tr.dataset.runningStatusUrl, {
                                headers: {
                                    'Accept': 'application/json'
                                }
                            })
                            .then(function(r) {
                                return r.json();
                            })
                            .then(function(d) {
                                return d && d.cancellation && d.cancellation.finished;
                            })
                            .catch(function() {
                                return false;
                            });
                    })).then(function(done) {
                        if (done.some(Boolean)) {
                            clearInterval(timer);
                            window.location.reload();
                        }
                    });
                }, 5000);
            }
        });
    </script>
@endpush
