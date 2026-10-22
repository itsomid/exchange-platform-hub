@extends('dashboard.layout.master')

@section('title', 'انتقال پله‌های فروش به Binance — ربات معاملاتی')

@section('content')
    @php
        $pendingRows = $rows->where('pending_tiers', '>', 0);
        $insufficientRows = $pendingRows->filter(fn($r) => $r->shortfall !== null && $r->shortfall > 0);
    @endphp

    {{-- Page header --}}
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-4">
        <div>
            <h4 class="mb-1">انتقال پله‌های فروش از CoinEx به Binance</h4>
            <p class="text-muted mb-0" style="max-width: 56rem; line-height: 1.8;">
                پله‌های فروش باز ربات که هنوز به سفارش‌های CoinEx اشاره می‌کنند، به تفکیک ارز. برای هر ارز مقدار موردنیاز با
                موجودی آزاد Binance مقایسه می‌شود. با انتقال، برای هر پله یک سفارش Limit در Binance ثبت و همان رکورد پله
                ویرایش می‌شود؛ پله‌هایی که قیمت فعلی از تارگتشان عبور کرده با سفارش Market فروخته و مثل یک پله پرشده تسویه
                می‌شوند. پله‌های پرشده یا لغوشده دست نمی‌خورند.
            </p>
        </div>
        <span class="badge bg-label-primary rounded-pill px-3 py-2 align-self-center">
            <i class="fas fa-coins me-1"></i>
            {{ $pendingRows->count() }} ارز در انتظار انتقال
        </span>
    </div>

    @if ($balanceError)
        <div class="alert alert-danger d-flex align-items-center">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span>{{ $balanceError }}</span>
        </div>
    @endif

    {{-- Summary cards --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-start justify-content-between">
                    <div>
                        <span class="d-block text-muted mb-1">پله‌های در انتظار انتقال</span>
                        <h4 class="mb-0 font-number">{{ $pendingRows->sum('pending_tiers') }}</h4>
                        <small class="text-muted">{{ $pendingRows->count() }} ارز</small>
                    </div>
                    <span class="badge bg-label-primary rounded p-2"><i class="fa-light fa-layer-group fa-lg"></i></span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-start justify-content-between">
                    <div>
                        <span class="d-block text-muted mb-1">ارزهای با کمبود موجودی</span>
                        <h4 class="mb-0 font-number {{ $insufficientRows->count() ? 'text-danger' : '' }}">
                            {{ $balanceError ? '—' : $insufficientRows->count() }}</h4>
                        <small class="text-muted">باید در Binance تامین شوند</small>
                    </div>
                    <span class="badge bg-label-danger rounded p-2"><i class="fa-light fa-triangle-exclamation fa-lg"></i></span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-start justify-content-between">
                    <div>
                        <span class="d-block text-muted mb-1">پله‌هایی که تارگتشان خورده</span>
                        <h4 class="mb-0 font-number text-warning">{{ $pendingRows->sum('hit_tiers') }}</h4>
                        <small class="text-muted">با Market فروخته و تسویه می‌شوند</small>
                    </div>
                    <span class="badge bg-label-warning rounded p-2"><i class="fa-light fa-bullseye-arrow fa-lg"></i></span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-start justify-content-between">
                    <div>
                        <span class="d-block text-muted mb-1">پله‌های منتقل‌شده</span>
                        <h4 class="mb-0 font-number text-success">{{ $rows->sum('migrated_tiers') }}</h4>
                        <small class="text-muted">به Binance</small>
                    </div>
                    <span class="badge bg-label-success rounded p-2"><i class="fa-light fa-circle-check fa-lg"></i></span>
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
                            placeholder="نماد یا نام ارز، مثلاً AVAX">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label" for="stateFilter">وضعیت</label>
                    <select id="stateFilter" class="form-select">
                        <option value="">همه ارزها</option>
                        <option value="pending">در انتظار انتقال</option>
                        <option value="insufficient">کمبود موجودی</option>
                        <option value="done">منتقل‌شده</option>
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
                <table class="table table-hover align-middle mb-0" id="migrationTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">ارز</th>
                            <th class="text-nowrap text-center">کاربران / سفارش‌ها</th>
                            <th class="text-nowrap">پله‌ها<br><small class="text-muted fw-normal">در انتظار · تارگت‌خورده ·
                                    پرشده · منتقل‌شده</small></th>
                            <th class="text-nowrap">قیمت لحظه‌ای</th>
                            <th class="text-nowrap">مقدار موردنیاز</th>
                            <th class="text-nowrap">موجودی Binance<br><small class="text-muted fw-normal">آزاد /
                                    قفل</small></th>
                            <th class="text-nowrap">وضعیت موجودی</th>
                            <th class="pe-4 text-center">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php
                                $hasPending = $row->pending_tiers > 0;
                                $state = !$hasPending ? 'done' : ($row->shortfall > 0 ? 'insufficient' : 'pending');
                            @endphp
                            <tr class="{{ $hasPending ? '' : 'table-secondary opacity-75' }}"
                                data-search="{{ strtolower($row->symbol . ' ' . $row->currency->name . ' ' . $row->currency->persian_name) }}"
                                data-state="{{ $state }}">
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
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge bg-label-primary">در انتظار {{ $row->pending_tiers }}</span>
                                        @if ($row->hit_tiers > 0)
                                            <span class="badge bg-label-warning"
                                                title="{{ formatNumberTrimZeros($row->hit_amount, 8) }} {{ $row->symbol }}">تارگت‌خورده
                                                {{ $row->hit_tiers }}</span>
                                        @endif
                                        <span class="badge bg-label-success">پر {{ $row->filled_tiers }}</span>
                                        <span class="badge bg-label-info">منتقل {{ $row->migrated_tiers }}</span>
                                    </div>
                                </td>
                                <td class="font-number">
                                    @if ($row->price)
                                        {{ formatNumberTrimZeros($row->price, 8) }}
                                    @else
                                        <span class="text-muted" title="قیمت لحظه‌ای در کش موجود نیست">—</span>
                                    @endif
                                </td>
                                <td class="font-number fw-semibold">
                                    @if ($hasPending)
                                        {{ formatNumberTrimZeros($row->required, 8) }}
                                        <small class="text-muted fw-normal">{{ $row->symbol }}</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="font-number">
                                    @if ($row->available !== null)
                                        <div>{{ formatNumberTrimZeros($row->available, 8) }}</div>
                                        <small class="text-muted">قفل {{ formatNumberTrimZeros($row->frozen, 8) }}</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if (!$hasPending)
                                        <span class="badge bg-label-secondary">انتقال کامل شده</span>
                                    @elseif ($row->shortfall === null)
                                        <span class="badge bg-label-secondary">نامشخص</span>
                                    @elseif ($row->sufficient)
                                        <span class="badge bg-label-success"><i class="fas fa-check me-1"></i>کافی</span>
                                    @else
                                        <span class="badge bg-label-danger"><i class="fas fa-exclamation me-1"></i>کمبود
                                            <span class="font-number">{{ formatNumberTrimZeros($row->shortfall, 8) }}</span></span>
                                    @endif
                                </td>
                                <td class="pe-4 text-center text-nowrap">
                                    @if ($row->running)
                                        <span class="badge bg-label-warning">
                                            <i class="fas fa-spinner fa-spin me-1"></i>
                                            انتقال #{{ $row->running->id }} {{ $row->running->statusLabel() }}
                                        </span>
                                    @elseif ($hasPending)
                                        <button type="button" class="btn btn-sm btn-primary js-sell-migration"
                                            data-symbol="{{ $row->symbol }}"
                                            data-preview-url="{{ route('admin.bot.sell-migration.preview', $row->currency->id) }}"
                                            data-start-url="{{ route('admin.bot.sell-migration.start', $row->currency->id) }}">
                                            <i class="fas fa-right-left me-1"></i> ایجاد پله‌ها در Binance
                                        </button>
                                    @else
                                        <span class="badge bg-label-success">تکمیل</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="fa-light fa-circle-check fa-2x d-block mb-2 opacity-50"></i>
                                    هیچ پله فروش بازی در CoinEx باقی نمانده است.
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

    {{-- History of migration runs --}}
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">
                <i class="fa-light fa-clock-rotate-left me-1 text-muted"></i>
                سابقه انتقال‌ها
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
                            <th>پله‌ها</th>
                            <th>Limit ثبت‌شده</th>
                            <th>تکمیل با Market</th>
                            <th>ادغام / بستن</th>
                            <th>در انتظار ثبت</th>
                            <th>ناموفق</th>
                            <th>مقدار موردنیاز</th>
                            <th>ادمین / زمان</th>
                            <th class="pe-4 text-center">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($migrations as $m)
                            @php
                                $statusClass = match ($m->status) {
                                    'DONE' => 'bg-label-success',
                                    'FAILED' => 'bg-label-danger',
                                    default => 'bg-label-warning',
                                };
                            @endphp
                            <tr
                                @if (!$m->isFinished()) data-running-status-url="{{ route('admin.bot.sell-migration.status', $m->id) }}" @endif>
                                <td class="ps-4 font-number">{{ $m->id }}</td>
                                <td><strong>{{ strtoupper((string) $m->currency?->symbol) }}</strong></td>
                                <td>
                                    <span class="badge {{ $statusClass }}">{{ $m->statusLabel() }}</span>
                                    @if ($m->error)
                                        <i class="fas fa-exclamation-circle text-danger ms-1" title="{{ $m->error }}"></i>
                                    @endif
                                </td>
                                <td class="font-number">{{ $m->tiers_count }}</td>
                                <td class="font-number text-primary">{{ $m->placed_count }}</td>
                                <td class="font-number text-warning">{{ $m->completed_count }}</td>
                                <td class="font-number">{{ $m->merged_count }} / {{ $m->closed_count }}</td>
                                <td class="font-number {{ $m->waiting_count ? 'text-info fw-semibold' : '' }}">{{ (int) $m->waiting_count }}</td>
                                <td class="font-number {{ $m->failed_count ? 'text-danger fw-semibold' : '' }}"
                                    @if ($m->failed_count) title="{{ collect($m->details['tiers'] ?? [])->where('outcome', 'failed')->map(fn($t) => '#' . $t['sell_order_id'] . ': ' . ($t['error'] ?? ''))->implode(' | ') }}" @endif>
                                    {{ $m->failed_count }}</td>
                                <td class="font-number">{{ formatNumberTrimZeros($m->required_amount, 8) }}</td>
                                <td>
                                    <small>{{ $m->admin_label ?: '—' }}</small>
                                    <div><small
                                            class="text-muted">{{ \App\Helpers\DateFormatter::convertToPersianDate($m->created_at, '%Y/%m/%d H:i') }}</small>
                                    </div>
                                </td>
                                <td class="pe-4 text-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary text-nowrap js-migration-report"
                                        data-report-url="{{ route('admin.bot.sell-migration.report', $m->id) }}"
                                        data-title="گزارش انتقال #{{ $m->id }} — {{ strtoupper((string) $m->currency?->symbol) }}"
                                        @disabled(!$m->isFinished())>
                                        <i class="fa-light fa-file-lines me-1"></i> گزارش کامل
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted py-4">
                                    <i class="fa-light fa-inbox fa-lg d-block mb-2 opacity-50"></i>
                                    هنوز انتقالی انجام نشده است.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('dashboard.bot.sell-migration.partials.confirm-modal')
    @include('dashboard.bot.sell-migration.partials.report-modal')
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var searchEl = document.getElementById('currencySearch');
            var stateEl = document.getElementById('stateFilter');
            var rows = Array.prototype.slice.call(document.querySelectorAll('#migrationTable tbody tr[data-search]'));
            var noResult = document.getElementById('noFilterResult');
            var summary = document.getElementById('activeFilters');
            var summaryList = document.getElementById('activeFiltersList');
            var resultCount = document.getElementById('filterResultCount');

            function applyFilters() {
                var q = searchEl.value.trim().toLowerCase();
                var state = stateEl.value;
                var visible = 0;

                rows.forEach(function(tr) {
                    var rowState = tr.dataset.state;
                    var stateMatch = !state || rowState === state || (state === 'pending' && rowState === 'insufficient');
                    var match = (!q || tr.dataset.search.indexOf(q) !== -1) && stateMatch;
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
                                return d && d.migration && d.migration.finished;
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
