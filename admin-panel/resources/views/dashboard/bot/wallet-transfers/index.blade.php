@extends('dashboard.layout.master')
@section('title', 'واریز/برداشت‌های کیف‌پول ربات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>تعداد کل انتقال‌ها</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $totalCount }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="fa-light fa-right-left fa-lg"></i>
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
                            <span>تعداد انتقال‌های امروز</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $todayCount }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-warning rounded">
                            <i class="fa-light fa-calendar-day"></i>
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
                            <span>مجموع واریز به ربات</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 text-success">{{ formatNumberTrimZeros($totalDeposits) }}</h4>
                                <small>USDT</small>
                            </div>
                        </div>
                        <span class="badge bg-label-success rounded">
                            <i class="fa-light fa-arrow-down-to-bracket"></i>
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
                            <span>مجموع برداشت از ربات</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 text-danger">{{ formatNumberTrimZeros($totalWithdrawals) }}</h4>
                                <small>USDT</small>
                            </div>
                            <small class="text-muted">کارمزد دریافتی: {{ formatNumberTrimZeros($totalFees) }} USDT</small>
                        </div>
                        <span class="badge bg-label-danger rounded">
                            <i class="fa-light fa-arrow-up-from-bracket"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">فیلتر پیشرفته انتقال‌ها</h5>
                <div class="card-title-elements ms-auto">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="toggleAdvancedFilter">
                        <i class="fas fa-chevron-down me-1"></i> نمایش فیلترهای پیشرفته
                    </button>
                </div>
            </div>
            <form action="{{ route('admin.bot.wallet-transfer.index') }}" method="get" id="filterForm">
                <!-- Basic Filters Row -->
                <div class="row mb-3">
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                        <label class="form-label" for="direction">نوع انتقال:</label>
                        <select name="direction" class="form-select" id="direction">
                            <option value="">همه</option>
                            <option value="{{ \App\Models\Bot\BotWalletTransfer::DIRECTION_IN }}"
                                {{ request()->input('direction') == \App\Models\Bot\BotWalletTransfer::DIRECTION_IN ? 'selected' : '' }}>
                                واریز به ربات
                            </option>
                            <option value="{{ \App\Models\Bot\BotWalletTransfer::DIRECTION_OUT }}"
                                {{ request()->input('direction') == \App\Models\Bot\BotWalletTransfer::DIRECTION_OUT ? 'selected' : '' }}>
                                برداشت از ربات
                            </option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                        <label class="form-label" for="status">وضعیت:</label>
                        <select name="status" class="form-select" id="status">
                            <option value="">همه</option>
                            @foreach (\App\Enums\TransactionStatusEnum::cases() as $case)
                                <option value="{{ $case->value }}"
                                    {{ request()->input('status') == $case->value ? 'selected' : '' }}>
                                    {{ $case->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-6 col-md-8 col-sm-12 mb-2">
                        <label class="form-label" for="user">کاربر:</label>
                        <x-user-selection-component input-name="user" multiple="0"
                            selected="{{ request()->filled('user') ? request()->input('user') : '' }}"
                            selected-label="{{ request()->filled('user')
                                ? '(#' . request()->input('user') . ') ' . (\App\Models\User::find(request()->input('user'))?->fullname() ?? '') . ' - ' . (\App\Models\User::find(request()->input('user'))?->email ?? '')
                                : '' }}"></x-user-selection-component>
                    </div>
                </div>

                <!-- Date and Action Buttons Row -->
                <div class="row mb-3">
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                        <label class="form-label" for="date_from">از تاریخ:</label>
                        <input type="text" name="date_from" class="form-control" id="date_from" data-jdp
                            value="{{ request()->input('date_from') }}">
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                        <label class="form-label" for="date_to">تا تاریخ:</label>
                        <input type="text" name="date_to" class="form-control" id="date_to" data-jdp
                            value="{{ request()->input('date_to') }}">
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 mb-2 d-flex align-items-end">
                        <div class="d-flex flex-wrap gap-1">
                            <button class="btn btn-success btn-sm" type="submit">
                                <i class="fas fa-search me-1"></i> اعمال فیلتر
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="clearFilters">
                                <i class="fas fa-times me-1"></i> حذف فیلترها
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Advanced Filters (hidden by default) -->
                <div class="row mb-3" id="advancedFilters" style="display: none;">
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                        <label class="form-label" for="amount_min">حداقل مقدار (ناخالص):</label>
                        <input type="number" name="amount_min" class="form-control" id="amount_min" placeholder="0.00"
                            step="0.00000001" value="{{ request()->input('amount_min') }}">
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                        <label class="form-label" for="amount_max">حداکثر مقدار (ناخالص):</label>
                        <input type="number" name="amount_max" class="form-control" id="amount_max" placeholder="0.00"
                            step="0.00000001" value="{{ request()->input('amount_max') }}">
                    </div>
                </div>

                <!-- Active filter summary -->
                @if (request()->hasAny(['direction', 'status', 'user', 'date_from', 'date_to', 'amount_min', 'amount_max']))
                    <div class="alert alert-info d-flex align-items-center flex-wrap">
                        <i class="fas fa-info-circle me-2"></i>
                        <span class="me-2">فیلترهای فعال:</span>
                        <div class="d-flex flex-wrap gap-1">
                            @if (request()->filled('direction'))
                                <span class="badge bg-primary">
                                    نوع: {{ request()->input('direction') === \App\Models\Bot\BotWalletTransfer::DIRECTION_IN ? 'واریز به ربات' : 'برداشت از ربات' }}
                                </span>
                            @endif
                            @if (request()->filled('status'))
                                @php $selectedStatus = \App\Enums\TransactionStatusEnum::tryFrom(request()->input('status')); @endphp
                                <span class="badge bg-primary">وضعیت: {{ $selectedStatus?->label() ?? request()->input('status') }}</span>
                            @endif
                            @if (request()->filled('user'))
                                @php $selectedUser = \App\Models\User::find(request()->input('user')); @endphp
                                @if ($selectedUser)
                                    <span class="badge bg-primary">کاربر: (#{{ $selectedUser->id }}) {{ $selectedUser->fullname() }} - {{ $selectedUser->email }}</span>
                                @else
                                    <span class="badge bg-primary">کاربر: #{{ request()->input('user') }}</span>
                                @endif
                            @endif
                            @if (request()->filled('date_from'))
                                <span class="badge bg-primary">از: {{ request()->input('date_from') }}</span>
                            @endif
                            @if (request()->filled('date_to'))
                                <span class="badge bg-primary">تا: {{ request()->input('date_to') }}</span>
                            @endif
                            @if (request()->filled('amount_min'))
                                <span class="badge bg-warning">مقدار ≥ {{ request()->input('amount_min') }}</span>
                            @endif
                            @if (request()->filled('amount_max'))
                                <span class="badge bg-warning">مقدار ≤ {{ request()->input('amount_max') }}</span>
                            @endif
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست واریز/برداشت‌های کیف‌پول ربات</h5>
            </div>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>
                            @php
                                $currentParams = request()->except('sortById');
                                $currentSortDirection = request()->input('sortById', 'desc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.bot.wallet-transfer.index', array_merge($currentParams, ['sortById' => $newSortDirection])) }}"
                                class="text-black">
                                ID
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>
                        </th>
                        <th>کاربر</th>
                        <th>نوع</th>
                        <th>مقدار ناخالص</th>
                        <th>کارمزد</th>
                        <th>مقدار خالص</th>
                        <th>وضعیت</th>
                        <th>تاریخ و زمان</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @if ($transfers->isEmpty())
                        <tr>
                            <td colspan="9" class="text-center">انتقالی یافت نشد.</td>
                        </tr>
                    @else
                        @foreach ($transfers as $transfer)
                            <tr>
                                <td>{{ $transfer->id }}</td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <a href="{{ route('admin.inquiry.user-details', ['user' => $transfer->user]) }}"
                                            class="text-heading text-truncate">
                                            <span class="fw-medium">{{ $transfer->user->email }}</span>
                                        </a>
                                        <small>{{ $transfer->user->username }}</small>
                                    </div>
                                </td>
                                <td>
                                    @if ($transfer->direction === \App\Models\Bot\BotWalletTransfer::DIRECTION_IN)
                                        <span class="badge bg-label-success">
                                            <i class="fa-light fa-arrow-down-to-bracket me-1"></i> واریز به ربات
                                        </span>
                                    @else
                                        <span class="badge bg-label-danger">
                                            <i class="fa-light fa-arrow-up-from-bracket me-1"></i> برداشت از ربات
                                        </span>
                                    @endif
                                </td>
                                <td class="font-number" dir="ltr">
                                    <h6 class="mb-0">{{ formatNumberTrimZeros($transfer->gross_amount) }}</h6>
                                </td>
                                <td class="font-number" dir="ltr">
                                    <h6 class="mb-0 text-warning">{{ formatNumberTrimZeros($transfer->fee) }}</h6>
                                </td>
                                <td class="font-number" dir="ltr">
                                    <h6 class="mb-0">{{ formatNumberTrimZeros($transfer->net_amount) }}</h6>
                                </td>
                                <td>
                                    <span class="badge bg-label-{{ $transfer->status->color() }}">{{ $transfer->status->label() }}</span>
                                </td>
                                <td class="font-number">
                                    {{ \App\Helpers\DateFormatter::convertToPersianDate($transfer->created_at, 'H:i:s %Y/%m/%d') }}<br>
                                    <small class="text-muted">{{ $transfer->created_at->format('Y/m/d') }}</small>
                                </td>
                                <td>
                                    <a href="" class="btn btn-icon btn-text-secondary" data-bs-toggle="modal"
                                        data-bs-target="#bot-wallet-transfer-{{ $transfer->id }}">
                                        <i class="fa-light fa-memo-circle-info fa-xl"></i>
                                    </a>
                                    <x-transaction-modal :modalId="'bot-wallet-transfer-' . $transfer->id"
                                        :title="'تراکنش‌های انتقال #' . $transfer->id" :user="$transfer->user"
                                        :transactions="$transfer->transactions" route-name="admin.transaction.index"
                                        route-param="bot_wallet_transfer_id" :route-param-value="$transfer->id" />
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                {{ $transfers->appends(request()->all())->links() }}
            </div>
        </div>
    </div>
@endsection

@section('vendor-script')
    <script>
        $(document).ready(function() {
            $('.table-responsive .modal').each(function() {
                $(this).appendTo('body');
            });

            $('#clearFilters').on('click', function() {
                window.location.href = "{{ route('admin.bot.wallet-transfer.index') }}";
            });

            $('#toggleAdvancedFilter').on('click', function() {
                const $section = $('#advancedFilters');
                const $btn = $(this);
                if ($section.is(':visible')) {
                    $section.slideUp();
                    $btn.html('<i class="fas fa-chevron-down me-1"></i> نمایش فیلترهای پیشرفته');
                } else {
                    $section.slideDown();
                    $btn.html('<i class="fas fa-chevron-up me-1"></i> پنهان کردن فیلترهای پیشرفته');
                }
            });

            const advancedInputs = ['amount_min', 'amount_max'];
            const hasAdvancedFilter = advancedInputs.some(function(name) {
                const el = document.querySelector('#advancedFilters [name="' + name + '"]');
                return el && el.value.trim() !== '';
            });
            if (hasAdvancedFilter) {
                $('#advancedFilters').show();
                $('#toggleAdvancedFilter').html('<i class="fas fa-chevron-up me-1"></i> پنهان کردن فیلترهای پیشرفته');
            }
        });
    </script>
@endsection
