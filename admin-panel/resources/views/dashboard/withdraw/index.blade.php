@extends('dashboard.layout.master')
@section('title', 'مدیریت برداشت ها')
@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>

                                تعداد برداشت ها
                                @if (request('status'))
                                    ({{ \App\Enums\WithdrawalStatusEnum::TYPE_LABEL[request('status')] }})
                                @else
                                    <span>(همه)</span>
                                @endif
                            </span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $withdraws->total() }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-danger rounded p-2">
                            <i class="fa-light fa-money-bill-wave fa-lg"></i>
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
                            <span>تعداد برداشت های امروز</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $todayWithdrawalsCount }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-warning rounded">
                            <i class="fa-light fa-money-bill-wave"></i>
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
                            <span>ارزش برداشت های امروز</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ formatNumberTrimZeros($totalWithdrawalsValue) }}</h4>
                                <small>USDT</small>
                            </div>
                        </div>
                        <span class="badge bg-label-warning rounded">
                            <i class="fa-light fa-money-bill-wave"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body bg-success">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-white">کاربران با بیشترین برداشت امروز</span>
                            <div class="d-flex align-items-baseline my-1">
                                <small class="text-white mx-2">مجموع: </small>
                                <h4 class="mb-0 me-2 text-primary">{{ formatNumber($totalTopUsersWithdrawals, 2) }}</h4>
                                <small class="text-primary">USDT</small>
                            </div>
                        </div>
                        <ul class="list-unstyled avatar-group d-flex my-0">
                            @if (count($topUsers))
                                @foreach ($topUsers as $topUser)
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-html='true'
                                        data-bs-placement="top" class="avatar pull-up"
                                        title="<span class='fw-medium'>نام:</span>
                                                    {{ $topUser['user']->fullname() }}</span>
                                                    <br> <span class='fw-medium'>شناسه کاربری:</span>
                                                    <span class='fw-medium font-monospace'>({{ $topUser['user']->id }}#)</span>
                                                    <br> <span class='fw-medium'>نام کاربری:</span>
                                                    <span class='fw-medium font-monospace'>({{ $topUser['user']->username }})</span>
                                                    <br> <span class='fw-medium'>مجموع برداشت:</span>
                                                    <span class='fw-medium font-monospace'>{{ formatNumberTrimZeros($topUser['totalWithdraw']) }}$</span>
                                                    ">
                                        <div class="avatar me-2">
                                            @php
                                                // Define your color array
                                                $colors = ['primary', 'info', 'danger', 'warning', 'success'];

                                                // Get a random index from the array
                                                $randomIndex = array_rand($colors);

                                                // Retrieve the color using the random index
                                                $randomColor = $colors[$randomIndex];
                                            @endphp
                                            <span
                                                class="avatar-initial rounded-circle bg-label-{{ $randomColor }}">{{ $topUser['user']->avatar_user_name }}</span>
                                        </div>
                                    </li>
                                @endforeach
                            @else
                                بدون برداشت
                            @endif

                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">خروجی اکسل</h5>
            <form class="row mt-3 d-flex align-items-end"
                action="{{ route('admin.withdrawal.excel-export', request()->query()) }}" method="POST">
                @csrf
                <div class="col-md-2 user_role">
                    <label class="form-label" for="UserRole">از آیدی :</label>
                    <input type="number" class="form-control" placeholder="آیدی کاربر">
                </div>
                <div class="col-md-2 user_role">
                    <label class="form-label" for="UserRole">تا آیدی :</label>
                    <input type="number" class="form-control" placeholder="آیدی کاربر">
                </div>
                <div class="col-md-2 mt-2">
                    <button class="btn btn-success class ">دانلود خروجی اکسل</button>
                </div>
            </form>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">فیلتر پیشرفته برداشت‌ها</h5>
            </div>
            <form action="{{ route('admin.withdrawal.index') }}" method="get" id="filterForm">
                <!-- Basic Filters Row -->
                <div class="row mb-3">
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                        <label class="form-label" for="status">وضعیت برداشت:</label>
                        <select name="status" class="form-select" id="status">
                            <option value="">همه</option>
                            @foreach (\App\Enums\WithdrawalStatusEnum::cases() as $case)
                                <option value="{{ $case->value }}"
                                    {{ request()->has('status') && request()->input('status') == $case->value ? 'selected' : '' }}>
                                    {{ $case->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                        <label class="form-label" for="currency">کوین:</label>
                        <select name="currency" class="form-select" id="currency">
                            <option value="">همه کوین‌ها</option>
                            @foreach ($currencies as $currency)
                                <option value="{{ $currency->symbol }}"
                                    {{ request()->has('currency') && request()->input('currency') == $currency->symbol ? 'selected' : '' }}>
                                    {{ $currency->symbol }} - {{ $currency->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                        <label class="form-label" for="currencyChain">شبکه:</label>
                        <select name="currencyChain" class="form-select" id="currencyChain">
                            <option value="">همه شبکه‌ها</option>
                            @foreach ($chains as $chain)
                                <option value="{{ $chain->value }}"
                                    {{ request()->has('currencyChain') && request()->input('currencyChain') == $chain->value ? 'selected' : '' }}>
                                    {{ $chain->chain_name() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-12 col-12 mb-2">
                        <label class="form-label d-none d-lg-block">&nbsp;</label>
                        <div class="d-flex flex-wrap gap-1 justify-content-start">
                            <button class="btn btn-success btn-sm flex-fill" type="submit" style="min-width: 70px;">
                                <i class="fas fa-search me-1"></i>جستجو
                            </button>
                            <button class="btn btn-outline-secondary btn-sm flex-fill" type="button" id="clearFilters"
                                style="min-width: 70px;">
                                <i class="fas fa-times me-1"></i>پاک کردن
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Second Row for User and Search Filters -->
                <div class="row mb-3">
                    <div class="col-lg-6 col-md-6 col-sm-12 mb-2">
                        <label class="form-label" for="user">کاربر:</label>
                        <x-user-selection-component input-name="user" multiple="0"
                            selected="{{ request()->filled('user') && $withdraws->isNotEmpty() && $withdraws[0]->user ? $withdraws[0]->user->id : '' }}"
                            selected-label="{{ request()->filled('user') && $withdraws->isNotEmpty() && $withdraws[0]->user
                                ? '(' . $withdraws[0]->user->id . '#) ' . $withdraws[0]->user->fullname() . ' | ' . $withdraws[0]->user->email
                                : '' }}"></x-user-selection-component>
                    </div>

                    <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                        <label class="form-label" for="address">آدرس برداشت:</label>
                        <input type="text" name="address" id="address" class="form-control font-monospace"
                            placeholder="آدرس کیف پول..." value="{{ request()->input('address') }}">
                    </div>

                    <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                        <label class="form-label" for="transactionHash">هش تراکنش (TxID):</label>
                        <input type="text" name="transactionHash" id="transactionHash"
                            class="form-control font-monospace" placeholder="هش تراکنش..."
                            value="{{ request()->input('transactionHash') }}">
                    </div>
                </div>

                <!-- Filter Summary (Show active filters) -->
                @if (request()->hasAny(['status', 'currency', 'currencyChain', 'user', 'address', 'transactionHash']))
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-info d-flex align-items-center">
                                <i class="fas fa-info-circle me-2"></i>
                                <span class="me-2">فیلترهای فعال:</span>
                                <div class="d-flex flex-wrap gap-1">
                                    @if (request()->filled('status'))
                                        @php
                                            $selectedStatus = \App\Enums\WithdrawalStatusEnum::tryFrom(
                                                request()->input('status'),
                                            );
                                        @endphp
                                        <span class="badge bg-primary">وضعیت:
                                            {{ $selectedStatus?->label() ?? request()->input('status') }}</span>
                                    @endif
                                    @if (request()->filled('currency'))
                                        @php
                                            $selectedCurrency = $currencies->firstWhere(
                                                'symbol',
                                                request()->input('currency'),
                                            );
                                        @endphp
                                        <span class="badge bg-primary">کوین:
                                            {{ $selectedCurrency ? $selectedCurrency->symbol . ' - ' . $selectedCurrency->name : request()->input('currency') }}</span>
                                    @endif
                                    @if (request()->filled('currencyChain'))
                                        @php
                                            $selectedChain = \App\Enums\CurrencyChainEnum::tryFrom(
                                                request()->input('currencyChain'),
                                            );
                                        @endphp
                                        <span class="badge bg-primary">شبکه:
                                            {{ $selectedChain?->chain_name() ?? request()->input('currencyChain') }}</span>
                                    @endif
                                    @if (request()->filled('user'))
                                        @php
                                            $selectedUser =
                                                $withdraws->isNotEmpty() && $withdraws[0]->user
                                                    ? $withdraws[0]->user
                                                    : \App\Models\User::find(request()->input('user'));
                                        @endphp
                                        @if ($selectedUser)
                                            <span class="badge bg-primary">کاربر: (#{{ $selectedUser->id }})
                                                {{ $selectedUser->fullname() }} - {{ $selectedUser->email }}</span>
                                        @else
                                            <span class="badge bg-primary">کاربر: #{{ request()->input('user') }}</span>
                                        @endif
                                    @endif
                                    @if (request()->filled('address'))
                                        <span class="badge bg-success">آدرس:
                                            {{ Str::limit(request()->input('address'), 20) }}</span>
                                    @endif
                                    @if (request()->filled('transactionHash'))
                                        <span class="badge bg-warning">TxID:
                                            {{ Str::limit(request()->input('transactionHash'), 20) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست برداشت ها</h5>
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
                            <a href="{{ route('admin.withdrawal.index', array_merge($currentParams, ['sortById' => $newSortDirection])) }}"
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
                        <th>Coin</th>
                        <th>شبکه</th>
                        <th>
                            @php
                                $currentParams = request()->except('sortByAmount');
                                $newSortDirection = request()->input('sortByAmount') == 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.withdrawal.index', array_merge($currentParams, ['sortByAmount' => $newSortDirection])) }}"
                                class="text-black">
                                مقدار
                                @if (request()->input('sortByAmount') == 'asc')
                                    <span>&uarr;</span>
                                @else
                                    <span>&darr;</span>
                                @endif
                            </a>
                        </th>
                        <th>
                            @php
                                $currentParams = request()->except('sortByTotalFee');
                                $currentSortDirection = request()->input('sortByTotalFee', 'desc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.withdrawal.index', array_merge($currentParams, ['sortByTotalFee' => $newSortDirection])) }}"
                                class="text-black">
                                کارمزد برداشت <br> (فی صرافی مرجع + بیتکس روم)
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>

                        </th>
                        <th>ارزش</th>
                        <th>آدرس برداشت</th>
                        <th> هش تراکنش (TxID)</th>
                        <th>
                            @php
                                $currentParams = request()->except('sortByCreatedAt');
                                $currentSortDirection = request()->input('sortByCreatedAt', 'desc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.withdrawal.index', array_merge($currentParams, ['sortByCreatedAt' => $newSortDirection])) }}"
                                class="text-black">
                                زمان درخواست
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>
                        </th>
                        <th>
                            @php
                                $currentParams = request()->except('sortByConfirmedAt');
                                $currentSortDirection = request()->input('sortByConfirmedAt', 'desc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.withdrawal.index', array_merge($currentParams, ['sortByConfirmedAt' => $newSortDirection])) }}"
                                class="text-black">
                                زمان تکمیل برداشت
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>
                        </th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @if ($withdraws->isEmpty())
                        <tr>
                            <td colspan="11" class="text-center">برداشتی یافت نشد.</td>
                        </tr>
                    @else
                        @foreach ($withdraws as $withdraw)
                            <tr>
                                <td>{{ $withdraw->id }}</td>

                                <td>
                                    <div class="d-flex flex-column">
                                        <a href="{{ route('admin.inquiry.user-details', ['user' => $withdraw->user]) }}"
                                            class="text-heading text-truncate">
                                            <span class="fw-medium">{{ $withdraw->user->email }}</span>
                                        </a>
                                        <small>{{ $withdraw->user->username }}</small>
                                    </div>
                                </td>
                                <td class="text-heading fw-medium">
                                    <img src="{{ asset($withdraw->currency->coinLogo()) }}"
                                        class="rounded-circle img-fluid" width="25">
                                    <small class="ms-1">{{ $withdraw->currency_symbol }}</small>

                                </td>
                                <td>
                                    <smal>{{ $withdraw->currencyChain->chain }}</smal>
                                </td>
                                <td class="font-number" dir="ltr">
                                    <h6 class="mb-0">{{ formatNumberTrimZeros($withdraw->amount) }}</h6>
                                </td>
                                <td class="font-number" dir="ltr">
                                    <h6 class="mb-0">{{ formatNumberTrimZeros($withdraw->total_fee) }}</h6>
                                </td>
                                <td dir="ltr">
                                    <h6 class="font-number text-heading mb-0">
                                        <span class="ms-1">{{ formatNumberTrimZeros($withdraw->usdt_value) }}</span>
                                        <small class="text-muted">USDT</small>
                                    </h6>
                                </td>
                                <td class="font-number">
                                    <h6 class="mb-0 d-flex">
                                        @if ($withdraw->explorer_address_url)
                                            <a href="javascript:void(0);" class="clipboard-btn mx-1"
                                                data-clipboard-action="copy"
                                                data-clipboard-target="#withdraw{{ $withdraw->address }}">
                                                <i class="fa-regular fa-clone"></i>
                                            </a>

                                            <input type="hidden" value="{{ $withdraw->address }}"
                                                id="withdraw{{ $withdraw->address }}" class="form-control text-left"
                                                aria-label="Username" readonly>

                                            <a href="{{ $withdraw->explorer_address_url }}" target="_blank">
                                                <small>{{ shorten_hash($withdraw->address) }}</small>
                                            </a>
                                        @else
                                            <small>N/A Address</small>
                                        @endif
                                    </h6>
                                </td>

                                <td class="font-number">
                                    <h6 class="mb-0">
                                        @if ($withdraw->explorer_tx_url && $withdraw->transaction_hash)
                                            <a href="javascript:void(0);" class="clipboard-btn mx-1"
                                                data-clipboard-action="copy"
                                                data-clipboard-target="#deposit{{ $withdraw->transaction_hash }}">
                                                <i class="fa-regular fa-clone"></i>
                                            </a>
                                            <input type="hidden" value="{{ $withdraw->transaction_hash }}"
                                                id="deposit{{ $withdraw->transaction_hash }}"
                                                class="form-control text-left" placeholder="کد معرف شما"
                                                aria-label="Username" readonly>
                                            <a href="{{ $withdraw->explorer_tx_url }}" target="_blank" class="me-1">
                                                <small>{{ shorten_hash($withdraw->transaction_hash) }}</small>
                                            </a>
                                        @else
                                            <small>N/A TxID</small>
                                        @endif
                                    </h6>

                                </td>
                                <td class="font-number">
                                    {{ \App\Helpers\DateFormatter::convertToPersianDate($withdraw->created_at, 'H:i:s %Y/%m/%d') }}<br>
                                    <small class="text-muted ">{{ $withdraw->created_at->format('Y/m/d') }}</small>
                                </td>
                                <td class="font-number">
                                    {{ \App\Helpers\DateFormatter::convertToPersianDate($withdraw->confirmed_at, 'H:i:s %Y/%m/%d') }}<br>
                                    <small class="text-muted">
                                        {{ $withdraw->confirmed_at ? $withdraw->confirmed_at->format('Y/m/d') : '' }}
                                    </small>
                                </td>

                                <td>
                                    <span
                                        class="badge bg-label-{{ $withdraw->status->color() }}">{{ $withdraw->status->label() }}</span>
                                </td>
                                <td>

                                    <a href="#" class="btn btn-icon btn-text-secondary" data-bs-toggle="modal"
                                        data-bs-target="#withdraw-modal-{{ $withdraw->id }}">
                                        <i class="fa-regular fa-eye fa-xl"></i>
                                    </a>

                                    <div class="modal fade" id="withdraw-modal-{{ $withdraw->id }}" tabindex="-1"
                                        aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div
                                                class="modal-content border-3 border-{{ $withdraw->status === \App\Enums\WithdrawalStatusEnum::COMPLETED
                                                    ? 'success'
                                                    : ($withdraw->status === \App\Enums\WithdrawalStatusEnum::FAILED
                                                        ? 'danger'
                                                        : ($withdraw->status === \App\Enums\WithdrawalStatusEnum::PENDING
                                                            ? 'warning'
                                                            : 'info')) }}">
                                                <div class="modal-header" dir="ltr">
                                                    <h5 class="modal-title font-number">Withdraw #{{ $withdraw->id }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    @include('dashboard.withdraw.withdrawal-detail-modal', [
                                                        'withdraw' => $withdraw,
                                                    ])
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-label-secondary"
                                                        data-bs-dismiss="modal">بستن
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if ($withdraw->status === \App\Enums\WithdrawalStatusEnum::COMPLETED)
                                        <a href="" class="btn btn-icon btn-text-secondary" data-bs-toggle="modal"
                                            data-bs-target="#withdraw-{{ $withdraw->id }}">
                                            <i class="fa-light fa-memo-circle-info fa-xl"></i>
                                        </a>
                                        <x-transaction-modal :modalId="'withdraw-' . $withdraw->id" :title="'تراکنش های برداشت #' . $withdraw->id" :user="$withdraw->user"
                                            :transactions="$withdraw->transactions" route-name="admin.transaction.index"
                                            route-param="withdrawal_id" :route-param-value="$withdraw->id" />
                                    @endif
                                    @if ($withdraw->status === \App\Enums\WithdrawalStatusEnum::PENDING)
                                        <a href="{{ route('admin.withdrawal.check-withdrawal', $withdraw->id) }}"
                                            class="btn btn-sm btn-icon btn-warning">
                                            <i class="fa-solid fa-rotate-right"></i>
                                        </a>
                                    @endif
                                    @if ($withdraw->status === \App\Enums\WithdrawalStatusEnum::AWAITING_APPROVAL)
                                        <a href="{{ route('admin.withdrawal.confirm-withdrawal', ['withdraw' => $withdraw]) }}"
                                            class="btn btn-icon btn-success me-2">
                                            <i class="fa-regular fa-badge-check fa-lg"></i>
                                        </a>
                                        <a href="{{ route('admin.withdrawal.cancel-withdrawal', ['withdraw' => $withdraw]) }}"
                                            class="btn btn-icon btn-danger">
                                            <i class="fa-regular fa-xmark fa-lg"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                {{ $withdraws->appends(request()->all())->links() }}
            </div>
        </div>

    </div>

@endsection
@section('vendor-script')
    <script>
        $(document).ready(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();

            // Handle copy functionality
            $('.clipboard-btn').on('click', function(e) {
                e.preventDefault();
                const btn = $(this);
                const icon = btn.find('i');
                const originalIcon = icon.attr('class');

                // Copy the text
                const targetId = btn.data('clipboard-target');
                const textToCopy = $(targetId).val();
                navigator.clipboard.writeText(textToCopy).then(() => {
                    // Change icon to tick
                    icon.removeClass(originalIcon).addClass('fa-solid fa-check');

                    // Change back to original icon after 2 seconds
                    setTimeout(() => {
                        icon.removeClass('fa-solid fa-check').addClass(originalIcon);
                    }, 2000);
                });
            });

            // Clear filters button
            $('#clearFilters').on('click', function() {
                window.location.href = "{{ route('admin.withdrawal.index') }}";
            });
        });
    </script>
@endsection
