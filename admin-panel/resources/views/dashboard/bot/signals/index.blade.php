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
                        <tr data-id="{{ $signal->id }}">
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
                            <td class="priority-cell">{{ $signal->priority }}</td>
                            <td>{{ formatNumberTrimZeros($signal->floor_price) }}</td>
                            <td>{{ formatNumberTrimZeros($signal->ceiling_price) }}</td>
                            <td>{{ $signal->max_allocation_percent }}٪</td>
                            <td>{{ $signal->sell_orders_count }}</td>
                            <td>
                                @if ($signal->is_active)
                                    <span class="badge bg-success">فعال</span>
                                @else
                                    <span class="badge bg-secondary">غیرفعال</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
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
                            <td colspan="10" class="text-center text-muted py-4">
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
        });
    </script>
@endpush
