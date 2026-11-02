@extends('dashboard.layout.master')
@section('title', 'تنظیمات ربات معامله‌گر اسپات')
@section('content')
    <!-- آمار کلی ربات‌ها -->
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>کل ارزهای دیجیتال</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $totalCurrencies }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="fa-light fa-coins fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>ربات‌های فعال</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $activeBots }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
                            <i class="fa-solid fa-robot fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>ربات‌های غیرفعال</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $inactiveBots }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-warning rounded p-2">
                            <i class="fa-solid fa-robot fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>ربات‌های پیکربندی شده</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $configuredBots }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                            <i class="fa-solid fa-gear fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-3">
                <i class="fa-solid fa-sliders me-2"></i>
                تنظیمات کلی
            </h5>
            <div class="row g-4">
                <div class="col-lg-4">
                    <form action="{{ route('admin.setting.spot-bot.bulk-interval') }}" method="POST"
                        class="h-100 d-flex flex-column">
                        @csrf
                        <label for="price_interval_seconds" class="form-label">
                            <i class="fa-solid fa-clock me-1"></i>
                            تغییر دسته‌ای بازه زمانی (ثانیه)
                        </label>
                        <div class="input-group">
                            <input type="number" class="form-control @error('price_interval_seconds') is-invalid @enderror"
                                id="price_interval_seconds" name="price_interval_seconds"
                                value="{{ old('price_interval_seconds', 60) }}" min="1" required>
                            <button type="submit" class="btn btn-primary text-nowrap">
                                اعمال روی همه
                            </button>
                        </div>
                        @error('price_interval_seconds')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </form>
                </div>
                <div class="col-lg-4">
                    <div class="h-100 d-flex flex-column">
                        <label class="form-label">
                            <i class="fa-solid fa-power-off me-1"></i>
                            وضعیت دسته‌ای بازارها
                        </label>
                        <div class="d-flex gap-2">
                            <form action="{{ route('admin.setting.spot-bot.bulk-disable') }}" method="POST"
                                class="flex-fill"
                                onsubmit="return confirm('آیا از غیرفعال کردن ربات همه بازارها اطمینان دارید؟');">
                                @csrf
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="fa-solid fa-ban me-1"></i>
                                    غیرفعال کردن همه
                                </button>
                            </form>
                            <button type="button" class="btn btn-success flex-fill" data-bs-toggle="modal"
                                data-bs-target="#bulkActivateModal">
                                <i class="fa-solid fa-check me-1"></i>
                                فعال کردن کلی
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <form action="{{ route('admin.setting.spot-bot.cancel-inactive-orders') }}" method="POST"
                        class="h-100 d-flex flex-column"
                        onsubmit="return confirm('سفارش‌های باز ربات برای همه کوین‌های غیرفعال بسته شود؟');">
                        @csrf
                        <label class="form-label">
                            <i class="fa-solid fa-rectangle-xmark me-1"></i>
                            سفارش‌های باز ربات
                        </label>
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="fa-solid fa-rectangle-xmark me-1"></i>
                            بستن سفارش‌های کوین‌های غیرفعال
                        </button>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="card-title header-elements d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="m-0 me-2">
                    <i class="fa-solid fa-robot me-2"></i>
                    تنظیمات ربات معامله‌گر اسپات
                </h5>
                <form method="GET" action="{{ route('admin.setting.spot-bot.index') }}"
                    class="d-flex align-items-center gap-2">
                    <label for="sort" class="form-label mb-0 text-nowrap">مرتب‌سازی</label>
                    <select name="sort" id="sort" class="form-select form-select-sm w-auto"
                        onchange="this.form.submit()">
                        <option value="symbol" @selected($sort === 'symbol')>نماد</option>
                        <option value="popular" @selected($sort === 'popular')>محبوبیت کاربران</option>
                    </select>
                </form>
            </div>


            @if ($currencies->total())
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>
                                    <i class="fa-solid fa-coins me-1"></i>
                                    ارز دیجیتال
                                </th>
                                @if ($sort === 'popular')
                                    <th>
                                        <i class="fa-solid fa-chart-simple me-1"></i>
                                        معاملات کاربران
                                    </th>
                                @endif
                                <th>
                                    <i class="fa-solid fa-toggle-on me-1"></i>
                                    وضعیت ربات
                                </th>
                                <th>
                                    <i class="fa-solid fa-clock me-1"></i>
                                    بازه زمانی (ثانیه)
                                </th>
                                <th>
                                    <i class="fa-solid fa-percentage me-1"></i>
                                    مارجین
                                </th>
                                <th>
                                    <i class="fa-solid fa-user-robot me-1"></i>
                                    کاربر صوری
                                </th>
                                <th>
                                    <i class="fa-solid fa-cogs me-1"></i>
                                    عملیات
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($currencies as $currency)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-3">
                                                <img src="{{ asset($currency->coinLogo()) }}" class="img-fluid"
                                                    width="50px">
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $currency->name }}</h6>
                                                <small class="text-muted">{{ $currency->symbol }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    @if ($sort === 'popular')
                                        <td>
                                            <span
                                                class="badge bg-label-primary">{{ number_format($currency->user_trades_count) }}</span>
                                        </td>
                                    @endif
                                    <td>
                                        @if ($currency->spotBotSetting && $currency->spotBotSetting->is_active)
                                            <span class="badge bg-success">
                                                <i class="fa-solid fa-robot me-1"></i>
                                                فعال
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                <i class="fa-solid fa-robot me-1"></i>
                                                غیرفعال
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($currency->spotBotSetting)
                                            <span class="badge bg-label-info">
                                                {{ $currency->spotBotSetting->price_interval_seconds }} ثانیه
                                            </span>
                                        @else
                                            <span class="text-muted">تنظیم نشده</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($currency->spotBotSetting)
                                            <span class="badge bg-label-primary">
                                                {{ $currency->spotBotSetting->order_margin }}%
                                            </span>
                                        @else
                                            <span class="text-muted">تنظیم نشده</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($currency->spotBotSetting && $currency->spotBotSetting->fakeUser)
                                            <span class="badge bg-label-secondary">
                                                <i class="fa-solid fa-user me-1"></i>
                                                {{ $currency->spotBotSetting->fakeUser->username }}
                                            </span>
                                        @else
                                            <span class="text-muted">انتخاب نشده</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary js-open-spot-bot-settings"
                                            data-url="{{ route('admin.setting.spot-bot.edit', $currency) }}"
                                            data-title="تنظیمات {{ $currency->name }}">
                                            <i class="fa-solid fa-edit me-1"></i>
                                            تنظیمات
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($currencies->hasPages())
                    <div class="mt-3">
                        {{ $currencies->onEachSide(1)->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fa-solid fa-robot fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">هیچ ارز دیجیتالی یافت نشد</h5>
                    <p class="text-muted">برای استفاده از ربات معامله‌گر، ابتدا ارزهای دیجیتال را اضافه کنید.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="spotBotSettingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="spotBotSettingModalTitle">تنظیمات ربات</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="spotBotSettingModalBody"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bulkActivateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('admin.setting.spot-bot.bulk-activate') }}" method="POST" id="bulkActivateForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">فعال کردن کلی</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">کوین‌های تیک‌خورده فعال می‌شوند. کوین‌هایی که از قبل فعال هستند در لیست
                            مشخص شده‌اند و دوباره ذخیره نمی‌شوند.</p>
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                            <input type="search" class="form-control" id="activateCoinSearch"
                                placeholder="جستجوی نام یا نماد">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="activateSelectVisible">
                                <label class="form-check-label" for="activateSelectVisible">انتخاب موارد قابل
                                    مشاهده</label>
                            </div>
                        </div>
                        <div id="activateCoinList" class="border rounded" style="max-height: 420px; overflow: auto;">
                            @foreach ($activationCurrencies as $currency)
                                @php $isActive = $currency->spotBotSetting && $currency->spotBotSetting->is_active; @endphp
                                <label
                                    class="d-flex align-items-center gap-3 border-bottom px-3 py-2 mb-0 activate-coin-item"
                                    data-search="{{ strtolower($currency->name . ' ' . $currency->symbol) }}">
                                    <input class="form-check-input mt-0" type="checkbox" name="currency_ids[]"
                                        value="{{ $currency->id }}" @disabled($isActive)>
                                    <img src="{{ $currency->coinLogo() }}" alt="" width="28" height="28"
                                        class="rounded">
                                    <span class="flex-grow-1">
                                        <span class="fw-medium">{{ $currency->name }}</span>
                                        <small class="text-muted d-block">{{ $currency->symbol }}</small>
                                    </span>
                                    @if ($isActive)
                                        <span class="badge bg-success">فعال</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-success">ذخیره</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const settingModalEl = document.getElementById('spotBotSettingModal');
            const settingModal = new bootstrap.Modal(settingModalEl);
            const settingBody = document.getElementById('spotBotSettingModalBody');
            const settingTitle = document.getElementById('spotBotSettingModalTitle');

            document.querySelectorAll('.js-open-spot-bot-settings').forEach(function(button) {
                button.addEventListener('click', function() {
                    openSpotBotSettings(button);
                });
            });

            settingModalEl.addEventListener('hidden.bs.modal', function() {
                settingBody.innerHTML = '';
            });

            document.getElementById('activateCoinSearch').addEventListener('input', filterActivateCoins);
            document.getElementById('activateSelectVisible').addEventListener('change', function() {
                document.querySelectorAll('#activateCoinList .activate-coin-item').forEach(function(item) {
                    if (item.classList.contains('d-none')) {
                        return;
                    }
                    const checkbox = item.querySelector('input[type="checkbox"]');
                    if (!checkbox.disabled) {
                        checkbox.checked = document.getElementById('activateSelectVisible').checked;
                    }
                });
            });

            document.getElementById('bulkActivateForm').addEventListener('submit', function(event) {
                if (!this.querySelector('input[name="currency_ids[]"]:checked')) {
                    event.preventDefault();
                    Toastify({
                        text: 'حداقل یک کوین را انتخاب کنید.',
                        duration: 5000,
                        gravity: 'top',
                        position: 'right',
                        style: {
                            background: '#EA5455'
                        }
                    }).showToast();
                }
            });

            function openSpotBotSettings(button) {
                settingTitle.textContent = button.dataset.title;
                settingBody.innerHTML = '<div class="text-center py-5 text-muted">در حال بارگذاری...</div>';
                settingModal.show();

                fetch(button.dataset.url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    }
                }).then(function(response) {
                    if (!response.ok) {
                        throw new Error('load failed');
                    }
                    return response.text();
                }).then(function(html) {
                    settingBody.innerHTML = html;
                    initSpotBotUserSelect(settingBody);
                    bindSpotBotSettingForm(settingBody);
                }).catch(function() {
                    settingBody.innerHTML =
                        '<div class="alert alert-danger mb-0">بارگذاری تنظیمات انجام نشد.</div>';
                });
            }

            function initSpotBotUserSelect(container) {
                const select = container.querySelector('select.js-user-select');
                if (!select || typeof $.fn.select2 !== 'function' || $(select).hasClass(
                        'select2-hidden-accessible')) {
                    return;
                }

                $(select).select2({
                    ajax: {
                        url: select.getAttribute('src'),
                        dataType: 'json',
                        delay: 250,
                        processResults: function(data) {
                            return {
                                results: data.map(function(user) {
                                    return {
                                        id: user.id,
                                        text: '(' + user.id + '#) ' + user.first_name + ' ' +
                                            user.last_name + ' | ' + user.email
                                    };
                                })
                            };
                        },
                        cache: true
                    },
                    dropdownParent: $('#spotBotSettingModal'),
                    minimumInputLength: 2,
                    placeholder: 'جهت انتخاب کاربر کلیک کنید',
                    allowClear: true,
                    width: '100%'
                });
            }

            function bindSpotBotSettingForm(container) {
                const form = container.querySelector('#spotBotSettingForm');
                const minOrderSize = container.querySelector('#spot_bot_min_order_size');
                const maxOrderSize = container.querySelector('#spot_bot_max_order_size');

                function validateOrderSizes() {
                    const minValue = parseFloat(minOrderSize.value) || 0;
                    const maxValue = parseFloat(maxOrderSize.value) || 0;
                    maxOrderSize.setCustomValidity(minValue > maxValue && maxValue > 0 ?
                        'حداکثر اندازه سفارش باید بیشتر از حداقل باشد' :
                        '');
                }

                minOrderSize.addEventListener('input', validateOrderSizes);
                maxOrderSize.addEventListener('input', validateOrderSizes);

                const cancelButton = container.querySelector('#spotBotCancelOrders');
                if (cancelButton) {
                    cancelButton.addEventListener('click', function() {
                        if (!confirm('سفارش‌های باز ربات این کوین در اردربوک بسته شود؟')) {
                            return;
                        }

                        cancelButton.disabled = true;
                        fetch(cancelButton.dataset.url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .content,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        }).then(async function(response) {
                            const data = await response.json().catch(function() {
                                return {};
                            });
                            Toastify({
                                text: data.message || 'بستن سفارش‌ها انجام نشد.',
                                duration: response.ok ? 3000 : 5000,
                                gravity: 'top',
                                position: 'right',
                                style: {
                                    background: response.ok ? '#28C76F' : '#EA5455'
                                }
                            }).showToast();
                            cancelButton.disabled = false;
                        }).catch(function() {
                            Toastify({
                                text: 'بستن سفارش‌ها انجام نشد.',
                                duration: 5000,
                                gravity: 'top',
                                position: 'right',
                                style: {
                                    background: '#EA5455'
                                }
                            }).showToast();
                            cancelButton.disabled = false;
                        });
                    });
                }

                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    validateOrderSizes();
                    if (!form.reportValidity()) {
                        return;
                    }

                    const submitButton = form.querySelector('#spotBotSettingSubmit');
                    const errorBox = form.querySelector('#spotBotSettingErrors');
                    submitButton.disabled = true;
                    errorBox.classList.add('d-none');
                    errorBox.innerHTML = '';

                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: new FormData(form)
                    }).then(async function(response) {
                        const data = await response.json().catch(function() {
                            return {};
                        });
                        if (!response.ok) {
                            const messages = data.errors ?
                                Object.values(data.errors).flat() : [data.message ||
                                    'ذخیره تنظیمات انجام نشد.'
                                ];
                            errorBox.innerHTML = '<ul class="mb-0">' + messages.map(function(
                                message) {
                                return '<li>' + message + '</li>';
                            }).join('') + '</ul>';
                            errorBox.classList.remove('d-none');
                            submitButton.disabled = false;
                            return;
                        }
                        window.location.reload();
                    }).catch(function() {
                        errorBox.textContent = 'ذخیره تنظیمات انجام نشد.';
                        errorBox.classList.remove('d-none');
                        submitButton.disabled = false;
                    });
                });
            }

            function filterActivateCoins() {
                const query = document.getElementById('activateCoinSearch').value.trim().toLowerCase();
                document.querySelectorAll('#activateCoinList .activate-coin-item').forEach(function(item) {
                    const matches = !query || item.dataset.search.includes(query);
                    item.classList.toggle('d-none', !matches);
                });
            }
        });
    </script>
@endpush
