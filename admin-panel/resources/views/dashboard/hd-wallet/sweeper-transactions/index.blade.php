@extends('dashboard.layout.master')
@section('title', 'تراکنش‌های Sweeper')
@section('content')

    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="card-title mb-1">
                    <i class="fa-regular fa-arrows-rotate me-2"></i>
                    همگام‌سازی تراکنش‌های Sweeper
                </h5>
                <small class="text-secondary">
                    دریافت تراکنش‌های sweep با وضعیت broadcasted از hd-wallet-sweeper و ذخیره در جدول حسابداری
                </small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary" id="syncSweeperTransactionsBtn">
                    <i class="fa-regular fa-cloud-arrow-down me-1"></i>
                    دریافت تراکنش‌های Sweep
                </button>
                <button type="button" class="btn btn-success" id="createAccountingTransactionsBtn"
                    @if (($stats['pending_accounting'] ?? 0) === 0) disabled @endif>
                    <i class="fa-regular fa-file-invoice-dollar me-1"></i>
                    ثبت تراکنش‌های حسابداری
                    <span class="badge bg-white text-success ms-1" id="pendingAccountingBadge">{{ number_format($stats['pending_accounting'] ?? 0) }}</span>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-secondary small mb-1">تعداد رکورد ذخیره‌شده</div>
                        <div class="fw-semibold fs-5" id="syncedTotalCount">{{ number_format($stats['total']) }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-secondary small mb-1">ثبت‌شده در transactions</div>
                        <div class="fw-semibold fs-5 text-success" id="accountedCount">{{ number_format($stats['accounted'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-secondary small mb-1">در انتظار ثبت تراکنش</div>
                        <div class="fw-semibold fs-5 text-warning" id="pendingAccountingCount">{{ number_format($stats['pending_accounting'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border rounded p-3 h-100">
                        <div class="text-secondary small mb-1">آخرین همگام‌سازی</div>
                        <div class="fw-semibold" id="lastSyncedAt">
                            @if ($stats['last_synced_at'])
                                {{ \Carbon\Carbon::parse($stats['last_synced_at'])->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">لیست تراکنش‌های ذخیره‌شده</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>شبکه</th>
                            <th>نماد</th>
                            <th>نوع</th>
                            <th>مبلغ</th>
                            <th>فی</th>
                            <th>از</th>
                            <th>به</th>
                            <th>Tx Hash</th>
                            <th>وضعیت حسابداری</th>
                            <th>Broadcast At</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            @php
                                $isAccounted = $log->hasAccountingTransactions();
                            @endphp
                            <tr data-log-id="{{ $log->id }}">
                                <td>{{ $log->id }}</td>
                                <td>{{ $log->network }}</td>
                                <td>{{ $log->symbol }}</td>
                                <td>
                                    <span class="badge bg-label-secondary">{{ $log->type ?? '—' }}</span>
                                </td>
                                <td>{{ $log->amount ?? '—' }}</td>
                                <td>{{ $log->fee ?? '—' }}</td>
                                <td>
                                    <small class="font-monospace" title="{{ $log->from_address }}">
                                        {{ $log->from_address ? \Illuminate\Support\Str::limit($log->from_address, 12, '…') : '—' }}
                                    </small>
                                </td>
                                <td>
                                    <small class="font-monospace" title="{{ $log->to_address }}">
                                        {{ $log->to_address ? \Illuminate\Support\Str::limit($log->to_address, 12, '…') : '—' }}
                                    </small>
                                </td>
                                <td>
                                    <small class="font-monospace" title="{{ $log->tx_hash }}">
                                        {{ $log->tx_hash ? \Illuminate\Support\Str::limit($log->tx_hash, 14, '…') : '—' }}
                                    </small>
                                </td>
                                <td class="accounting-status-cell">
                                    @if ($isAccounted)
                                        <span class="badge bg-label-success">ثبت شده</span>
                                        <div class="small text-secondary mt-1">
                                            W:#{{ $log->withdrawal_transaction_id }} /
                                            F:#{{ $log->fee_transaction_id }}
                                        </div>
                                    @else
                                        <span class="badge bg-label-warning">ثبت نشده</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $log->broadcast_at?->format('Y-m-d H:i') ?? '—' }}
                                </td>
                                <td>
                                    @if ($isAccounted)
                                        @php
                                            $modalUser = $log->transactions->first()?->user ?? $exchangeUser;
                                        @endphp
                                        @if ($modalUser)
                                            <a href="" class="btn btn-icon btn-text-secondary" data-bs-toggle="modal"
                                                data-bs-target="#sweeper-tx-{{ $log->id }}">
                                                <i class="fa-light fa-memo-circle-info fa-xl"></i>
                                            </a>
                                            <x-transaction-modal
                                                :modalId="'sweeper-tx-' . $log->id"
                                                :title="'تراکنش‌های سوئیپر #' . $log->id"
                                                :user="$modalUser"
                                                :transactions="$log->transactions"
                                                route-name="admin.transaction.index"
                                                route-param="sweeper_tx_id"
                                                :route-param-value="$log->id" />
                                        @endif
                                    @else
                                        <button type="button"
                                            class="btn btn-sm btn-outline-success create-one-accounting-btn"
                                            data-url="{{ route('admin.hd-wallet.sweeper-transactions.create-accounting-one', $log) }}">
                                            <i class="fa-regular fa-plus me-1"></i>
                                            ثبت تراکنش
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center text-secondary py-4">
                                    هنوز تراکنشی همگام‌سازی نشده است. دکمه بالا را بزنید.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $logs->links() }}
            </div>
        </div>
    </div>

    <div class="modal fade" id="syncResultModal" tabindex="-1" aria-labelledby="syncResultModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title mb-1" id="syncResultModalLabel">
                            <i class="fa-regular fa-circle-check text-success me-2"></i>
                            نتیجه همگام‌سازی
                        </h5>
                        <small class="text-secondary" id="syncResultSubtitle">جزئیات عملیات sync با sweeper</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3 mb-4" id="syncResultSummaryCards"></div>
                    <div class="border rounded p-3 mb-4 bg-lighter">
                        <div class="d-flex flex-wrap gap-3 small" id="syncResultMeta"></div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-semibold mb-2">بر اساس شبکه</div>
                                <div id="syncBreakdownNetwork" class="d-flex flex-column gap-2"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-semibold mb-2">بر اساس نماد</div>
                                <div id="syncBreakdownSymbol" class="d-flex flex-column gap-2"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="fw-semibold mb-2">بر اساس نوع</div>
                                <div id="syncBreakdownType" class="d-flex flex-column gap-2"></div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="fw-semibold mb-2">نمونه تراکنش‌های جدید</div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>شبکه</th>
                                        <th>نماد</th>
                                        <th>نوع</th>
                                        <th>مبلغ</th>
                                        <th>Tx Hash</th>
                                    </tr>
                                </thead>
                                <tbody id="syncCreatedSamplesBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div>
                        <div class="fw-semibold mb-2">نمونه تراکنش‌های به‌روزرسانی‌شده</div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>شبکه</th>
                                        <th>نماد</th>
                                        <th>نوع</th>
                                        <th>مبلغ</th>
                                        <th>Tx Hash</th>
                                    </tr>
                                </thead>
                                <tbody id="syncUpdatedSamplesBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">بستن</button>
                    <button type="button" class="btn btn-primary" id="syncResultReloadBtn">
                        <i class="fa-regular fa-arrows-rotate me-1"></i>
                        بروزرسانی لیست
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="accountingResultModal" tabindex="-1" aria-labelledby="accountingResultModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title mb-1" id="accountingResultModalLabel">
                            <i class="fa-regular fa-file-invoice-dollar text-success me-2"></i>
                            نتیجه ثبت تراکنش‌های حسابداری
                        </h5>
                        <small class="text-secondary">برای هر رکورد: Withdrawal/SWEEPER + Fee/SWEEPER</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3 mb-3" id="accountingResultSummaryCards"></div>
                    <div id="accountingResultErrors" class="d-none">
                        <div class="fw-semibold mb-2">خطاها</div>
                        <ul class="list-group list-group-flush" id="accountingResultErrorsList"></ul>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal" id="accountingResultCloseBtn">باشه</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const syncBtn = document.getElementById('syncSweeperTransactionsBtn');
            const accountingBtn = document.getElementById('createAccountingTransactionsBtn');
            const syncModalEl = document.getElementById('syncResultModal');
            const accountingModalEl = document.getElementById('accountingResultModal');
            if (!syncBtn || !syncModalEl || !accountingModalEl) return;

            const syncModal = new bootstrap.Modal(syncModalEl);
            const accountingModal = new bootstrap.Modal(accountingModalEl);
            let shouldReloadOnHide = false;

            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const jsonHeaders = {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            };

            const showToast = (text, isError = false) => {
                Toastify({
                    text,
                    duration: isError ? 5000 : 3000,
                    close: true,
                    gravity: 'top',
                    position: 'right',
                    stopOnFocus: true,
                    style: { background: isError ? '#EA5455' : '#28C76F' },
                }).showToast();
            };

            const formatNumber = (value) => Number(value || 0).toLocaleString('en-US');
            const shorten = (value, max = 14) => {
                if (!value) return '—';
                const text = String(value);
                return text.length > max ? text.slice(0, max) + '…' : text;
            };

            const renderBreakdown = (containerId, items) => {
                const el = document.getElementById(containerId);
                const entries = Object.entries(items || {});
                if (!entries.length) {
                    el.innerHTML = '<span class="text-secondary small">موردی نیست</span>';
                    return;
                }
                el.innerHTML = entries.map(([key, count]) => `
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="badge bg-label-secondary">${key}</span>
                        <span class="fw-semibold">${formatNumber(count)}</span>
                    </div>
                `).join('');
            };

            const renderSamples = (tbodyId, samples) => {
                const tbody = document.getElementById(tbodyId);
                if (!samples || !samples.length) {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-center text-secondary py-3">موردی در این همگام‌سازی نبود</td></tr>`;
                    return;
                }
                tbody.innerHTML = samples.map((item) => `
                    <tr>
                        <td>${item.network || '—'}</td>
                        <td>${item.symbol || '—'}</td>
                        <td><span class="badge bg-label-secondary">${item.type || '—'}</span></td>
                        <td>${item.amount || '—'}</td>
                        <td><small class="font-monospace" title="${item.tx_hash || ''}">${shorten(item.tx_hash)}</small></td>
                    </tr>
                `).join('');
            };

            const fillSyncResultModal = (data) => {
                document.getElementById('syncResultSubtitle').textContent =
                    `فیلتر: status=${data.filter?.status || 'broadcasted'}, type=${data.filter?.type || 'sweep'} · مدت: ${data.duration_human || '—'}`;

                document.getElementById('syncResultSummaryCards').innerHTML = `
                    <div class="col-6 col-md-3"><div class="border rounded p-3 h-100 text-center"><div class="text-secondary small mb-1">دریافت‌شده</div><div class="fs-4 fw-bold text-primary">${formatNumber(data.fetched)}</div></div></div>
                    <div class="col-6 col-md-3"><div class="border rounded p-3 h-100 text-center"><div class="text-secondary small mb-1">جدید</div><div class="fs-4 fw-bold text-success">${formatNumber(data.created)}</div></div></div>
                    <div class="col-6 col-md-3"><div class="border rounded p-3 h-100 text-center"><div class="text-secondary small mb-1">به‌روزرسانی</div><div class="fs-4 fw-bold text-warning">${formatNumber(data.updated)}</div></div></div>
                    <div class="col-6 col-md-3"><div class="border rounded p-3 h-100 text-center"><div class="text-secondary small mb-1">رد شده</div><div class="fs-4 fw-bold text-secondary">${formatNumber(data.skipped)}</div></div></div>
                `;

                document.getElementById('syncResultMeta').innerHTML = `
                    <div><span class="text-secondary">کل در DB:</span> <strong>${formatNumber(data.total)}</strong></div>
                    <div><span class="text-secondary">کل در sweeper:</span> <strong>${formatNumber(data.remote_total_count)}</strong></div>
                    <div><span class="text-secondary">صفحات:</span> <strong>${formatNumber(data.pages_fetched)}</strong> × ${formatNumber(data.page_limit)}</div>
                    <div><span class="text-secondary">شروع:</span> <strong>${data.started_at || '—'}</strong></div>
                    <div><span class="text-secondary">پایان:</span> <strong>${data.last_synced_at || '—'}</strong></div>
                `;

                renderBreakdown('syncBreakdownNetwork', data.breakdown?.by_network);
                renderBreakdown('syncBreakdownSymbol', data.breakdown?.by_symbol);
                renderBreakdown('syncBreakdownType', data.breakdown?.by_type);
                renderSamples('syncCreatedSamplesBody', data.samples?.created);
                renderSamples('syncUpdatedSamplesBody', data.samples?.updated);
            };

            const fillAccountingResultModal = (data) => {
                document.getElementById('accountingResultSummaryCards').innerHTML = `
                    <div class="col-4"><div class="border rounded p-3 text-center"><div class="text-secondary small mb-1">جدید</div><div class="fs-4 fw-bold text-success">${formatNumber(data.created)}</div></div></div>
                    <div class="col-4"><div class="border rounded p-3 text-center"><div class="text-secondary small mb-1">ردشده</div><div class="fs-4 fw-bold text-secondary">${formatNumber(data.skipped)}</div></div></div>
                    <div class="col-4"><div class="border rounded p-3 text-center"><div class="text-secondary small mb-1">ناموفق</div><div class="fs-4 fw-bold text-danger">${formatNumber(data.failed)}</div></div></div>
                `;

                const errorsWrap = document.getElementById('accountingResultErrors');
                const errorsList = document.getElementById('accountingResultErrorsList');
                const errors = data.errors || [];
                if (errors.length) {
                    errorsWrap.classList.remove('d-none');
                    errorsList.innerHTML = errors.map((item) => `
                        <li class="list-group-item px-0">
                            <span class="badge bg-label-danger me-1">#${item.id}</span>
                            <span class="small">${item.message}</span>
                        </li>
                    `).join('');
                } else {
                    errorsWrap.classList.add('d-none');
                    errorsList.innerHTML = '';
                }
            };

            const markRowAccounted = (row, data) => {
                if (!row) return;
                // Reload so the transaction modal (server-rendered) becomes available.
                window.location.reload();
            };

            const reloadOnHide = (modalEl) => {
                modalEl.addEventListener('hidden.bs.modal', function () {
                    if (shouldReloadOnHide) {
                        window.location.reload();
                    }
                });
            };
            reloadOnHide(syncModalEl);
            reloadOnHide(accountingModalEl);

            document.getElementById('syncResultReloadBtn').addEventListener('click', function () {
                shouldReloadOnHide = true;
                syncModal.hide();
            });

            syncBtn.addEventListener('click', function () {
                const originalHtml = syncBtn.innerHTML;
                syncBtn.disabled = true;
                syncBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>در حال دریافت...';

                fetch(@json(route('admin.hd-wallet.sweeper-transactions.sync')), {
                    method: 'POST',
                    headers: jsonHeaders,
                })
                    .then(async (response) => {
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'همگام‌سازی ناموفق بود.');
                        }
                        return data;
                    })
                    .then((data) => {
                        showToast(data.message || 'همگام‌سازی با موفقیت انجام شد.');
                        if (data.data) {
                            const totalEl = document.getElementById('syncedTotalCount');
                            const lastEl = document.getElementById('lastSyncedAt');
                            if (totalEl && typeof data.data.total !== 'undefined') {
                                totalEl.textContent = formatNumber(data.data.total);
                            }
                            if (lastEl && data.data.last_synced_at) {
                                lastEl.textContent = data.data.last_synced_at;
                            }
                            fillSyncResultModal(data.data);
                            shouldReloadOnHide = true;
                            syncModal.show();
                        }
                    })
                    .catch((error) => showToast(error.message || 'خطا در همگام‌سازی.', true))
                    .finally(() => {
                        syncBtn.disabled = false;
                        syncBtn.innerHTML = originalHtml;
                    });
            });

            accountingBtn.addEventListener('click', function () {
                const originalHtml = accountingBtn.innerHTML;
                accountingBtn.disabled = true;
                accountingBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>در حال ثبت...';

                fetch(@json(route('admin.hd-wallet.sweeper-transactions.create-accounting')), {
                    method: 'POST',
                    headers: jsonHeaders,
                })
                    .then(async (response) => {
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok || !data.success) {
                            throw new Error(data.message || 'ثبت تراکنش‌ها ناموفق بود.');
                        }
                        return data;
                    })
                    .then((data) => {
                        showToast(data.message || 'ثبت تراکنش‌ها انجام شد.');
                        fillAccountingResultModal(data.data || {});
                        if (typeof data.data?.pending_accounting !== 'undefined') {
                            document.getElementById('pendingAccountingCount').textContent = formatNumber(data.data.pending_accounting);
                            document.getElementById('pendingAccountingBadge').textContent = formatNumber(data.data.pending_accounting);
                        }
                        if (typeof data.data?.accounted !== 'undefined') {
                            document.getElementById('accountedCount').textContent = formatNumber(data.data.accounted);
                        }
                        shouldReloadOnHide = true;
                        accountingModal.show();
                    })
                    .catch((error) => showToast(error.message || 'خطا در ثبت تراکنش‌ها.', true))
                    .finally(() => {
                        accountingBtn.disabled = false;
                        accountingBtn.innerHTML = originalHtml;
                    });
            });

            document.querySelectorAll('.create-one-accounting-btn').forEach((btn) => {
                btn.addEventListener('click', function () {
                    const url = btn.dataset.url;
                    const row = btn.closest('tr');
                    const originalHtml = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                    fetch(url, {
                        method: 'POST',
                        headers: jsonHeaders,
                    })
                        .then(async (response) => {
                            const data = await response.json().catch(() => ({}));
                            if (!response.ok || !data.success) {
                                throw new Error(data.message || 'ثبت تراکنش ناموفق بود.');
                            }
                            return data;
                        })
                        .then((data) => {
                            showToast(data.message || 'تراکنش‌ها ثبت شد.');
                            if ((data.data?.status || '') === 'created' || (data.data?.status || '') === 'skipped') {
                                markRowAccounted(row, data.data || {});
                            }
                        })
                        .catch((error) => {
                            showToast(error.message || 'خطا در ثبت تراکنش.', true);
                            btn.disabled = false;
                            btn.innerHTML = originalHtml;
                        });
                });
            });
        });
    </script>
@endpush
