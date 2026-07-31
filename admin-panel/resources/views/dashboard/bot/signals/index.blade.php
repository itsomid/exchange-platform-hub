@extends('dashboard.layout.master')

@section('title', 'سیگنال‌های ربات معاملاتی')

@section('vendor-style')
    <style>
        body.signals-dragging,
        body.signals-dragging * {
            cursor: grabbing !important;
        }

        #signalsTable tbody tr[data-id] td {
            transition: background-color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease,
                transform 0.18s ease, opacity 0.18s ease;
        }

        #signalsTable tbody .drag-handle {
            cursor: grab;
            width: 40px;
            text-align: center;
        }

        #signalsTable tbody tr.signal-row-chosen td {
            background: rgba(115, 103, 240, 0.08) !important;
        }

        #signalsTable tbody tr.signal-row-chosen .drag-handle,
        #signalsTable tbody tr.signal-row-dragging .drag-handle {
            color: #7367f0 !important;
        }

        #signalsTable tbody tr.signal-row-dragging td {
            background: #fff !important;
            border-top: 1px solid rgba(115, 103, 240, 0.22);
            border-bottom: 1px solid rgba(115, 103, 240, 0.22);
            box-shadow: 0 14px 32px rgba(47, 43, 61, 0.14);
        }

        #signalsTable tbody tr.signal-row-dragging td:first-child {
            border-right: 1px solid rgba(115, 103, 240, 0.22);
            border-top-right-radius: 14px;
            border-bottom-right-radius: 14px;
        }

        #signalsTable tbody tr.signal-row-dragging td:last-child {
            border-left: 1px solid rgba(115, 103, 240, 0.22);
            border-top-left-radius: 14px;
            border-bottom-left-radius: 14px;
        }

        #signalsTable tbody tr.signal-row-ghost td {
            background: rgba(115, 103, 240, 0.06) !important;
            border-top: 2px dashed rgba(115, 103, 240, 0.35);
            border-bottom: 2px dashed rgba(115, 103, 240, 0.35);
            opacity: 0.8;
        }

        [data-role="last-price"] {
            transition: transform 0.25s ease, filter 0.25s ease;
            will-change: transform, filter;
        }

        @keyframes priceFlashUp {
            0% {
                color: #16c784;
                filter: drop-shadow(0 0 0 rgba(25, 135, 84, 0));
                transform: scale(1);
            }

            35% {
                color: #16c784;
                filter: drop-shadow(0 0 0.15rem rgba(25, 135, 84, 0.35));
                transform: scale(1.18);
            }

            100% {
                color: inherit;
                filter: drop-shadow(0 0 0 rgba(25, 135, 84, 0));
                transform: scale(1);
            }
        }

        @keyframes priceFlashDown {
            0% {
                color: #dc3545;
                filter: drop-shadow(0 0 0 rgba(220, 53, 69, 0));
                transform: scale(1);
            }

            35% {
                color: #dc3545;
                filter: drop-shadow(0 0 0.15rem rgba(220, 53, 69, 0.35));
                transform: scale(1.18);
            }

            100% {
                color: inherit;
                filter: drop-shadow(0 0 0 rgba(220, 53, 69, 0));
                transform: scale(1);
            }
        }

        .price-flash-up {
            animation: priceFlashUp 2s ease;
        }

        .price-flash-down {
            animation: priceFlashDown 2s ease;
        }
    </style>
@endsection

@section('content')

    {{-- Filters Card --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.bot.signal.index') }}" method="GET" id="filterForm">
                <div class="row mb-3">
                    <div class="col-lg-4 col-md-5 col-sm-12 mb-2">
                        <label class="form-label" for="search">جستجو:</label>
                        <input type="text" name="search" id="search" class="form-control" placeholder="نام یا سیمبل ارز..."
                            value="{{ request('search') }}">
                    </div>
                    <div class="col-lg-2 col-md-3 col-sm-6 mb-2">
                        <label class="form-label" for="status">وضعیت:</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>فعال</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>غیرفعال</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                        <label class="form-label" for="sell_mode">نوع هدف فروش:</label>
                        <select name="sell_mode" id="sell_mode" class="form-select">
                            <option value="">همه</option>
                            <option value="percent" {{ request('sell_mode') === 'percent' ? 'selected' : '' }}>درصد تغییر قیمت
                            </option>
                            <option value="price" {{ request('sell_mode') === 'price' ? 'selected' : '' }}>قیمت مطلق</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 mb-2 d-flex align-items-end">
                        <div class="d-flex flex-wrap gap-1">
                            <button class="btn btn-success btn-sm" type="submit">
                                <i class="fas fa-search me-1"></i> جستجو
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="clearFilters">
                                <i class="fas fa-times me-1"></i> پاک کردن
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Active Filter Summary --}}
                @if (request()->hasAny(['search', 'status', 'sell_mode']))
                    <div class="alert alert-info d-flex align-items-center flex-wrap">
                        <i class="fas fa-info-circle me-2"></i>
                        <span class="me-2">فیلترهای فعال:</span>
                        <div class="d-flex flex-wrap gap-1">
                            @if (request()->filled('search'))
                                <span class="badge bg-primary">جستجو: {{ request('search') }}</span>
                            @endif
                            @if (request()->filled('status'))
                                <span class="badge bg-primary">وضعیت:
                                    {{ request('status') === 'active' ? 'فعال' : 'غیرفعال' }}</span>
                            @endif
                            @if (request()->filled('sell_mode'))
                                <span class="badge bg-primary">نوع هدف:
                                    {{ request('sell_mode') === 'percent' ? 'درصد' : 'قیمت مطلق' }}</span>
                            @endif
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست سیگنال‌ها</h5>
                <div class="card-title-elements ms-auto">
                    <a href="{{ route('admin.bot.signal.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> افزودن سیگنال
                    </a>
                </div>
            </div>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-striped align-middle" id="signalsTable">
                <thead>
                    <tr>
                        <th style="width:40px"></th>
                        <th>#</th>
                        <th>ارز</th>
                        <th>قیمت لحظه‌ای (USDT)</th>
                        <th>اولویت</th>
                        <th>کف قیمت (USDT)</th>
                        <th>سقف قیمت (USDT)</th>
                        <th>سقف تخصیص</th>
                        <th>تعداد سفارش فروش</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($signals as $signal)
                        @php
                            $market = $signal->currency?->baseMarket;
                            $lastPrice = $market?->activeExchangePrice?->price;
                        @endphp
                        <tr data-id="{{ $signal->id }}" @if ($market) data-market-id="{{ $market->id }}" @endif>
                            <td class="drag-handle text-muted" style="cursor:grab"><i class="fas fa-grip-vertical"></i></td>
                            <td>{{ $signal->id }}</td>
                            <td class="d-flex align-items-center gap-3">
                                @if ($signal->currency)
                                    <img src="{{ $signal->currency->coinLogo() }}" class="rounded-circle me-1" width="32"
                                        height="32" alt="{{ $signal->currency->symbol }}">
                                @endif
                                <div>
                                    <span>{{ $signal->currency?->symbol }}</span>
                                    <small class="d-block text-muted">{{ $signal->currency?->name }}</small>
                                </div>
                            </td>
                            <td>
                                <h4 class="font-number text-heading h5 mb-0">
                                    <span class="ms-1" data-role="last-price">
                                        {{ $lastPrice !== null ? formatNumberTrimZeros($lastPrice) : '—' }}
                                    </span>
                                    <small class="text-muted">USDT</small>
                                </h4>
                            </td>
                            <td class="priority-cell">{{ $signal->priority }}</td>
                            <td data-role="floor-price">{{ formatNumberTrimZeros($signal->floor_price) }}</td>
                            <td data-role="ceiling-price">{{ formatNumberTrimZeros($signal->ceiling_price) }}</td>
                            <td data-role="max-allocation">{{ $signal->max_allocation_percent }}٪</td>
                            <td data-role="sell-orders-count">{{ $signal->sell_orders_count }}</td>
                            <td>
                                @if ($signal->is_active)
                                    <span class="badge bg-success">فعال</span>
                                @else
                                    <span class="badge bg-secondary">غیرفعال</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button type="button"
                                        class="btn btn-sm btn-outline-info btn-quick-edit"
                                        title="ویرایش سریع"
                                        data-id="{{ $signal->id }}"
                                        data-symbol="{{ $signal->currency?->symbol }}"
                                        data-floor-price="{{ formatNumberTrimZeros($signal->floor_price) }}"
                                        data-ceiling-price="{{ formatNumberTrimZeros($signal->ceiling_price) }}"
                                        data-max-allocation="{{ $signal->max_allocation_percent }}"
                                        data-sell-mode="{{ $signal->sell_mode }}"
                                        data-sell-targets='@json($signal->sell_targets ?? [])'
                                        data-url="{{ route('admin.bot.signal.quick-update', $signal) }}">
                                        <i class="fas fa-bolt"></i>
                                    </button>

                                    <a href="{{ route('admin.bot.signal.edit', $signal) }}"
                                        class="btn btn-sm btn-outline-primary" title="ویرایش">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <form method="POST" action="{{ route('admin.bot.signal.toggle-status', $signal) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                            class="btn btn-sm {{ $signal->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                            title="{{ $signal->is_active ? 'غیرفعال‌کردن' : 'فعال‌کردن' }}">
                                            <i class="fas {{ $signal->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.bot.signal.destroy', $signal) }}"
                                        onsubmit="return confirm('آیا از حذف این سیگنال مطمئن هستید؟')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">
                                هیچ سیگنالی یافت نشد.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $signals->links() }}
        </div>
    </div>

    {{-- Quick Edit Modal --}}
    <div class="modal fade" id="quickEditModal" tabindex="-1" aria-labelledby="quickEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form id="quickEditForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="quickEditModalLabel">ویرایش سریع سیگنال</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="quickEditAlert" class="alert alert-danger d-none"></div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="qe_floor_price" class="form-label">کف قیمت (USDT) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="qe_floor_price" name="floor_price" required>
                                <div class="invalid-feedback" data-error="floor_price"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="qe_ceiling_price" class="form-label">سقف قیمت (USDT) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="qe_ceiling_price" name="ceiling_price" required>
                                <div class="invalid-feedback" data-error="ceiling_price"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="qe_max_allocation" class="form-label">سقف تخصیص (%) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" max="100" class="form-control"
                                    id="qe_max_allocation" name="max_allocation_percent" required>
                                <div class="invalid-feedback" data-error="max_allocation_percent"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="qe_sell_mode" class="form-label">نوع هدف فروش <span class="text-danger">*</span></label>
                            <select class="form-select" id="qe_sell_mode" name="sell_mode" required>
                                <option value="percent">درصد تغییر قیمت</option>
                                <option value="price">قیمت مطلق</option>
                            </select>
                            <div class="invalid-feedback" data-error="sell_mode"></div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">اهداف فروش</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="qeAddTarget">
                                <i class="fas fa-plus me-1"></i> افزودن هدف
                            </button>
                        </div>
                        <div class="invalid-feedback d-block mb-2" data-error="sell_targets"></div>

                        <div class="row g-1 mb-1">
                            <div class="col-6"><small class="text-muted">هدف (% یا قیمت)</small></div>
                            <div class="col-5"><small class="text-muted">سهم (%)</small></div>
                            <div class="col-1"></div>
                        </div>
                        <div id="qeTargetsContainer"></div>
                        <div class="mt-2">
                            <small class="text-muted">مجموع سهم‌ها: <span id="qeShareTotal">0</span>٪
                                <span id="qeShareTotalError" class="text-danger d-none"> — باید ۱۰۰٪ باشد</span>
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-primary" id="qeSubmitBtn">
                            <i class="fas fa-save me-1"></i> ذخیره
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const body = document.body;
            document.getElementById('clearFilters').addEventListener('click', function () {
                window.location.href = '{{ route('admin.bot.signal.index') }}';
            });

            const tbody = document.querySelector('#signalsTable tbody');
            Sortable.create(tbody, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'signal-row-ghost',
                chosenClass: 'signal-row-chosen',
                dragClass: 'signal-row-dragging',
                onStart: function () {
                    body.classList.add('signals-dragging');
                },
                onEnd: function () {
                    body.classList.remove('signals-dragging');
                    const order = Array.from(tbody.querySelectorAll('tr[data-id]'))
                        .map(tr => tr.dataset.id);

                    fetch('{{ route('admin.bot.signal.reorder') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({ order }),
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (!data.success) throw new Error();
                            tbody.querySelectorAll('tr[data-id]').forEach(tr => {
                                const id = tr.dataset.id;
                                if (data.priorities[id] !== undefined) {
                                    tr.querySelector('td.priority-cell').textContent = data.priorities[id];
                                }
                            });
                            Toastify({
                                text: 'ترتیب سیگنال‌ها با موفقیت ذخیره شد.',
                                duration: 3000,
                                close: true,
                                gravity: 'top',
                                position: 'right',
                                stopOnFocus: true,
                                style: { background: '#28C76F' },
                            }).showToast();
                        })
                        .catch(() => {
                            Toastify({
                                text: 'خطا در ذخیره ترتیب. لطفاً صفحه را رفرش کنید.',
                                duration: 5000,
                                close: true,
                                gravity: 'top',
                                position: 'right',
                                stopOnFocus: true,
                                style: { background: '#EA5455' },
                            }).showToast();
                        });
                },
            });

            // ── Quick Edit Modal ──────────────────────────────────────────────
            const quickEditModalEl = document.getElementById('quickEditModal');
            const quickEditModal = new bootstrap.Modal(quickEditModalEl);
            const quickEditForm = document.getElementById('quickEditForm');
            const qeTargetsContainer = document.getElementById('qeTargetsContainer');
            const qeAlert = document.getElementById('quickEditAlert');
            const qeSubmitBtn = document.getElementById('qeSubmitBtn');
            let qeTargetIndex = 0;
            let qeCurrentUrl = null;
            let qeCurrentRow = null;
            let qeCurrentBtn = null;

            const showToast = (text, ok = true) => {
                Toastify({
                    text,
                    duration: ok ? 3000 : 5000,
                    close: true,
                    gravity: 'top',
                    position: 'right',
                    stopOnFocus: true,
                    style: { background: ok ? '#28C76F' : '#EA5455' },
                }).showToast();
            };

            const clearQuickEditErrors = () => {
                qeAlert.classList.add('d-none');
                qeAlert.textContent = '';
                quickEditForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                quickEditForm.querySelectorAll('[data-error]').forEach(el => {
                    el.textContent = '';
                    el.classList.add('d-none');
                });
            };

            const qeRecalcTotal = () => {
                let total = 0;
                qeTargetsContainer.querySelectorAll('.qe-share-input').forEach(el => {
                    total += parseFloat(el.value) || 0;
                });
                total = Math.round(total * 100) / 100;
                document.getElementById('qeShareTotal').textContent = total;
                const err = document.getElementById('qeShareTotalError');
                if (Math.abs(total - 100) > 0.01) {
                    err.classList.remove('d-none');
                } else {
                    err.classList.add('d-none');
                }
            };

            const qeSellModeEl = document.getElementById('qe_sell_mode');

            const qeIsPriceMode = () => qeSellModeEl && qeSellModeEl.value === 'price';

            const qeSyncTriggerInputs = () => {
                const suffix = qeIsPriceMode() ? 'USDT' : 'سود';
                qeTargetsContainer.querySelectorAll('.qe-trigger-suffix').forEach(el => {
                    el.textContent = suffix;
                });
            };

            const qeAddTargetRow = (trigger = '', share = '') => {
                const i = qeTargetIndex++;
                const suffix = qeIsPriceMode() ? 'USDT' : 'سود';
                const html = `
                    <div class="qe-target-row row g-1 mb-1" data-index="${i}">
                        <div class="col-6">
                            <div class="input-group input-group-sm">
                                <input type="number" step="any" min="0" class="form-control form-control-sm qe-trigger-input"
                                    name="sell_targets[${i}][trigger]" value="${trigger}" required>
                                <span class="input-group-text qe-trigger-suffix">${suffix}</span>
                            </div>
                        </div>
                        <div class="col-5">
                            <input type="number" step="0.01" min="0.01" max="100"
                                class="form-control form-control-sm qe-share-input"
                                name="sell_targets[${i}][share]" value="${share}" required>
                        </div>
                        <div class="col-1">
                            <button type="button" class="btn btn-sm btn-outline-danger qe-remove-target w-100">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>`;
                qeTargetsContainer.insertAdjacentHTML('beforeend', html);
                qeRecalcTotal();
            };

            qeSellModeEl.addEventListener('change', qeSyncTriggerInputs);

            document.getElementById('qeAddTarget').addEventListener('click', () => {
                if (qeTargetsContainer.querySelectorAll('.qe-target-row').length >= 10) {
                    showToast('حداکثر ۱۰ هدف فروش مجاز است.', false);
                    return;
                }
                qeAddTargetRow();
            });

            qeTargetsContainer.addEventListener('click', (e) => {
                if (e.target.closest('.qe-remove-target')) {
                    const rows = qeTargetsContainer.querySelectorAll('.qe-target-row');
                    if (rows.length <= 1) {
                        showToast('حداقل یک هدف فروش لازم است.', false);
                        return;
                    }
                    e.target.closest('.qe-target-row').remove();
                    qeRecalcTotal();
                }
            });

            qeTargetsContainer.addEventListener('input', (e) => {
                if (e.target.classList.contains('qe-share-input')) {
                    qeRecalcTotal();
                }
            });

            document.querySelectorAll('.btn-quick-edit').forEach(btn => {
                btn.addEventListener('click', () => {
                    clearQuickEditErrors();
                    qeCurrentUrl = btn.dataset.url;
                    qeCurrentRow = btn.closest('tr');
                    qeCurrentBtn = btn;

                    const symbol = btn.dataset.symbol || '';
                    document.getElementById('quickEditModalLabel').textContent =
                        symbol ? `ویرایش سریع — ${symbol}` : 'ویرایش سریع سیگنال';

                    document.getElementById('qe_floor_price').value = btn.dataset.floorPrice || '';
                    document.getElementById('qe_ceiling_price').value = btn.dataset.ceilingPrice || '';
                    document.getElementById('qe_max_allocation').value = btn.dataset.maxAllocation || '';
                    document.getElementById('qe_sell_mode').value = btn.dataset.sellMode || 'percent';

                    let targets = [];
                    try {
                        const raw = btn.getAttribute('data-sell-targets') || '[]';
                        targets = JSON.parse(raw);
                        if (!Array.isArray(targets)) targets = [];
                    } catch (e) {
                        targets = [];
                    }

                    qeTargetsContainer.innerHTML = '';
                    qeTargetIndex = 0;
                    if (!targets.length) {
                        qeAddTargetRow();
                    } else {
                        targets.forEach(t => {
                            const trigger = t.trigger ?? t.target ?? '';
                            const share = t.share ?? '';
                            qeAddTargetRow(trigger, share);
                        });
                    }

                    quickEditModal.show();
                });
            });

            quickEditForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!qeCurrentUrl) return;

                clearQuickEditErrors();

                const sellTargets = [];
                qeTargetsContainer.querySelectorAll('.qe-target-row').forEach(row => {
                    sellTargets.push({
                        trigger: row.querySelector('.qe-trigger-input').value,
                        share: row.querySelector('.qe-share-input').value,
                    });
                });

                const payload = {
                    floor_price: document.getElementById('qe_floor_price').value,
                    ceiling_price: document.getElementById('qe_ceiling_price').value,
                    max_allocation_percent: document.getElementById('qe_max_allocation').value,
                    sell_mode: document.getElementById('qe_sell_mode').value,
                    sell_targets: sellTargets,
                };

                qeSubmitBtn.disabled = true;
                const originalHtml = qeSubmitBtn.innerHTML;
                qeSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> در حال ذخیره...';

                fetch(qeCurrentUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify(payload),
                })
                    .then(async (res) => {
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            if (res.status === 422 && data.errors) {
                                Object.entries(data.errors).forEach(([field, messages]) => {
                                    const msg = Array.isArray(messages) ? messages[0] : messages;
                                    const baseField = field.split('.')[0];
                                    const feedback = quickEditForm.querySelector(`[data-error="${field}"]`)
                                        || quickEditForm.querySelector(`[data-error="${baseField}"]`);
                                    if (feedback) {
                                        feedback.textContent = msg;
                                        feedback.classList.remove('d-none');
                                        feedback.classList.add('d-block');
                                    }
                                    const input = quickEditForm.querySelector(`[name="${field}"]`)
                                        || quickEditForm.querySelector(`[name="${baseField}"]`);
                                    if (input) input.classList.add('is-invalid');
                                });
                                const firstError = Object.values(data.errors)[0];
                                showToast(Array.isArray(firstError) ? firstError[0] : firstError, false);
                                return;
                            }
                            throw new Error(data.message || 'خطا در ذخیره');
                        }

                        if (!data.success) throw new Error();

                        const signal = data.signal;
                        if (qeCurrentRow) {
                            const floorCell = qeCurrentRow.querySelector('[data-role="floor-price"]');
                            const ceilingCell = qeCurrentRow.querySelector('[data-role="ceiling-price"]');
                            const allocCell = qeCurrentRow.querySelector('[data-role="max-allocation"]');
                            const countCell = qeCurrentRow.querySelector('[data-role="sell-orders-count"]');
                            if (floorCell) floorCell.textContent = signal.floor_price;
                            if (ceilingCell) ceilingCell.textContent = signal.ceiling_price;
                            if (allocCell) allocCell.textContent = signal.max_allocation_percent + '٪';
                            if (countCell) countCell.textContent = signal.sell_orders_count;
                        }

                        if (qeCurrentBtn) {
                            qeCurrentBtn.dataset.floorPrice = signal.floor_price;
                            qeCurrentBtn.dataset.ceilingPrice = signal.ceiling_price;
                            qeCurrentBtn.dataset.maxAllocation = signal.max_allocation_percent;
                            qeCurrentBtn.dataset.sellMode = signal.sell_mode;
                            qeCurrentBtn.dataset.sellTargets = JSON.stringify(signal.sell_targets || []);
                        }

                        quickEditModal.hide();
                        showToast(data.message || 'سیگنال با موفقیت ویرایش شد.');
                    })
                    .catch((err) => {
                        showToast(err.message || 'خطا در ذخیره تغییرات.', false);
                    })
                    .finally(() => {
                        qeSubmitBtn.disabled = false;
                        qeSubmitBtn.innerHTML = originalHtml;
                    });
            });

            // Live market price via Echo (same channel as markets list)
            if (typeof window.Echo === 'undefined') {
                console.warn('Echo is not initialized. Check VITE_REVERB/VITE_PUSHER env vars and frontend build.');
                return;
            }

            const marketRows = document.querySelectorAll('#signalsTable tr[data-market-id]');
            const marketState = new Map();

            const toNumber = (value) => {
                if (value === null || typeof value === 'undefined') {
                    return null;
                }

                const normalized = String(value).replace(/,/g, '').trim();
                if (normalized === '' || normalized === '—') {
                    return null;
                }

                const numeric = Number(normalized);
                return Number.isFinite(numeric) ? numeric : null;
            };

            const formatPrice = (value) => {
                const numeric = Number(value);
                if (!Number.isFinite(numeric)) {
                    return '—';
                }

                if (numeric === 0) {
                    return '0';
                }

                const absolute = Math.abs(numeric);
                if (absolute < 0.00000001) {
                    const decimals = Math.min(20, Math.max(8, Math.ceil(-Math.log10(absolute)) + 4));
                    return numeric
                        .toFixed(decimals)
                        .replace(/\.0+$/, '')
                        .replace(/(\.\d*?)0+$/, '$1');
                }

                return numeric.toLocaleString('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 8,
                });
            };

            const applyValueAnimation = (element, direction) => {
                if (!element || direction === 0) {
                    return;
                }

                const classToAdd = direction > 0 ? 'price-flash-up' : 'price-flash-down';

                element.classList.remove('price-flash-up', 'price-flash-down');
                void element.offsetWidth;
                element.classList.add(classToAdd);

                window.setTimeout(() => {
                    element.classList.remove('price-flash-up', 'price-flash-down');
                }, 2000);
            };

            marketRows.forEach((row) => {
                const marketId = row.getAttribute('data-market-id');
                if (!marketId) {
                    return;
                }

                const lastPriceElement = row.querySelector('[data-role="last-price"]');
                marketState.set(row, {
                    last: toNumber(lastPriceElement?.textContent),
                });

                window.Echo.channel(`market.${marketId}`).listen('MarketUpdated', (event) => {
                    if (typeof event.last === 'undefined' || !lastPriceElement) {
                        return;
                    }

                    const numericValue = toNumber(event.last);
                    if (numericValue === null) {
                        return;
                    }

                    const previousValue = marketState.get(row)?.last ?? null;
                    lastPriceElement.textContent = formatPrice(numericValue);

                    if (previousValue !== null && previousValue !== numericValue) {
                        applyValueAnimation(lastPriceElement, numericValue > previousValue ? 1 : -1);
                    }

                    marketState.set(row, { last: numericValue });
                });
            });
        });
    </script>
@endpush
