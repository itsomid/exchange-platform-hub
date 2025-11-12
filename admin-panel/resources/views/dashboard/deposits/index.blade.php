@extends('dashboard.layout.master')
@section('title', 'مدیریت واریزی ها')
@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-md-4 col-xxl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>تعداد واریزی ها</span>

                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $deposits->total() }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
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
                            <span>ارزش واریزی ها</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ formatNumberTrimZeros($totalDepositsValue) }}
                                    <small>USDT</small>
                                </h4>
                            </div>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
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
                            <span>تعداد واریزی های امروز</span>
                            <span
                                class="ms-2">({{ \App\Helpers\DateFormatter::convertToPersianDate(now(), '%d %B') }})</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $todayDepositsCount }}</h4>
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
                            <span>ارزش واریزی های امروز</span>
                            <span
                                class="ms-2">({{ \App\Helpers\DateFormatter::convertToPersianDate(now(), '%d %B') }})</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ formatNumberTrimZeros($todayDepositsValue) }}</h4>
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
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body bg-success">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-white">کاربران با بیشترین واریزی</span>
                            <div class="d-flex align-items-baseline my-1">
                                <small class="text-white mx-2">مجموع: </small>
                                <h4 class="mb-0 me-2 text-primary">{{ formatNumberTrimZeros($totalTopUsersDeposit, 2) }}</h4>
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
                                                    <br> <span class='fw-medium'>مجموع واریز:</span>
                                                    <span class='fw-medium font-monospace'>{{ formatNumberTrimZeros($topUser['totalDeposit']) }}$</span>
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
                                        {{--                                        <img class="rounded-circle" src="{{ $topUser['user']->avatar_url ?? 'http://127.0.0.1:8000/images/avatars/male/2.png' }}"> --}}
                                    </li>
                                @endforeach
                            @else
                                بدون واریز
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
                action="{{ route('admin.deposit.excel-export', request()->query()) }}" method="POST">
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
                <h5 class="m-0 me-2">فیلتر پیشرفته واریزی‌ها</h5>
            </div>
            <form action="{{ route('admin.deposit.index') }}" method="get" id="filterForm">
                <!-- Basic Filters Row -->
                <div class="row mb-3">
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                        <label class="form-label" for="status">وضعیت واریز:</label>
                        <select name="status" class="form-select" id="status">
                            <option value="">همه</option>
                            @foreach (\App\Enums\DepositStatusEnum::cases() as $case)
                                <option value="{{ $case->value }}"
                                    {{ request()->has('status') && request()->input('status') == $case->value ? 'selected' : '' }}>
                                    {{ \App\Enums\DepositStatusEnum::TYPE_LABEL[$case->value] }}
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
                            selected="{{ request()->filled('user') && $deposits->isNotEmpty() && $deposits[0]->user ? $deposits[0]->user->id : '' }}"
                            selected-label="{{ request()->filled('user') && $deposits->isNotEmpty() && $deposits[0]->user
                                ? '(' . $deposits[0]->user->id . '#) ' . $deposits[0]->user->fullname() . ' | ' . $deposits[0]->user->email
                                : '' }}"></x-user-selection-component>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                        <label class="form-label" for="address">آدرس واریز:</label>
                        <input type="text" name="address" id="address" class="form-control font-monospace" 
                            placeholder="آدرس کیف پول..." 
                            value="{{ request()->input('address') }}">
                    </div>
                    
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                        <label class="form-label" for="transactionHash">هش تراکنش (TxID):</label>
                        <input type="text" name="transactionHash" id="transactionHash" class="form-control font-monospace" 
                            placeholder="هش تراکنش..." 
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
                                            $selectedStatus = \App\Enums\DepositStatusEnum::tryFrom(
                                                request()->input('status'),
                                            );
                                        @endphp
                                        <span class="badge bg-primary">وضعیت:
                                            {{ $selectedStatus?->label() ?? \App\Enums\DepositStatusEnum::TYPE_LABEL[request()->input('status')] ?? request()->input('status') }}</span>
                                    @endif
                                    @if (request()->filled('currency'))
                                        @php
                                            $selectedCurrency = $currencies->firstWhere('symbol', request()->input('currency'));
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
                                            $selectedUser = $deposits->isNotEmpty() && $deposits[0]->user ? $deposits[0]->user : \App\Models\User::find(request()->input('user'));
                                        @endphp
                                        @if ($selectedUser)
                                            <span class="badge bg-primary">کاربر: (#{{ $selectedUser->id }})
                                                {{ $selectedUser->fullname() }} - {{ $selectedUser->email }}</span>
                                        @else
                                            <span class="badge bg-primary">کاربر: #{{ request()->input('user') }}</span>
                                        @endif
                                    @endif
                                    @if (request()->filled('address'))
                                        <span class="badge bg-success">آدرس: {{ Str::limit(request()->input('address'), 20) }}</span>
                                    @endif
                                    @if (request()->filled('transactionHash'))
                                        <span class="badge bg-warning">TxID: {{ Str::limit(request()->input('transactionHash'), 20) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </div>



    <div class="card mb-4">
        <div class="card-header">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست واریزی ها</h5>
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
                            <a href="{{ route('admin.deposit.index', array_merge($currentParams, ['sortById' => $newSortDirection])) }}"
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
                                $currentSortDirection = request()->input('sortByAmount', 'asc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.deposit.index', array_merge($currentParams, ['sortByAmount' => $newSortDirection])) }}"
                                class="text-black">
                                مقدار
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>
                        </th>
                        <th>ارزش</th>
                        <th>آدرس واریز</th>
                        <th>(TxID) لینک تراکنش</th>
                        <th>
                            @php
                                $currentParams = request()->except('sortByCreatedAt');
                                $currentSortDirection = request()->input('sortByCreatedAt', 'asc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.deposit.index', array_merge($currentParams, ['sortByCreatedAt' => $newSortDirection])) }}"
                                class="text-black">
                                تاریخ ایجاد
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
                                $currentSortDirection = request()->input('sortByConfirmedAt', 'asc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.deposit.index', array_merge($currentParams, ['sortByConfirmedAt' => $newSortDirection])) }}"
                                class="text-black">
                                تاریخ تایید (شبکه)
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
                    @if ($deposits->isEmpty())
                        <tr>
                            <td colspan="11" class="text-center">واریزی یافت نشد.</td>
                        </tr>
                    @else
                        @foreach ($deposits as $deposit)
                            <tr>
                                <td>{{ $deposit->id }}</td>

                                <td>
                                    <div class="d-flex flex-column">
                                        <a href="" class="text-heading text-truncate">
                                            <span class="fw-medium">{{ $deposit->user->email }}</span>
                                        </a>
                                        <small>{{ $deposit->user->username }}</small>
                                    </div>
                                </td>

                                <td class="text-heading fw-medium">
                                    @if ($deposit->currency)
                                        <img src="{{ asset($deposit->currency->coinLogo()) }}"
                                            class="rounded-circle img-fluid" width="30">
                                        {{ $deposit->currency_symbol }}
                                    @else
                                        <span class="text-muted">{{ $deposit->currency_symbol }}</span>
                                    @endif
                                </td>

                                <td>
                                    {{ $deposit->currencyChain->chain_name }}
                                </td>

                                <td class="font-number" dir="ltr">
                                    <h6 class="mb-0">{{ formatNumberTrimZeros($deposit->amount) }}</h6>
                                </td>

                                <td dir="ltr">
                                    <h6 class="font-number text-heading mb-0">
                                        @if ($deposit->usdt_value)
                                            <span class="ms-1">{{ formatNumberTrimZeros($deposit->usdt_value) }}</span>
                                            <small class="text-muted">USDT</small>
                                        @else
                                            <span>N/A</span>
                                        @endif
                                    </h6>
                                </td>

                                <td class="font-number">
                                    <h6 class="mb-0">
                                        @if ($deposit->explorer_address_url)
                                            <a href="javascript:void(0);" class="clipboard-btn mx-1"
                                                data-clipboard-action="copy"
                                                data-clipboard-target="#deposit{{ $deposit->address }}">
                                                <i class="fa-regular fa-clone"></i>
                                            </a>
                                            <input type="hidden" value="{{ $deposit->address }}"
                                                id="deposit{{ $deposit->address }}" class="form-control text-left"
                                                placeholder="آدرس واریز" aria-label="Username" readonly>

                                            <a href="{{ $deposit->explorer_address_url }}" target="_blank">
                                                <small>{{ shorten_hash($deposit->address) }}</small>
                                            </a>
                                        @elseif($deposit->address)
                                            <a href="javascript:void(0);" class="clipboard-btn mx-1"
                                                data-clipboard-action="copy"
                                                data-clipboard-target="#deposit{{ $deposit->address }}">
                                                <i class="fa-regular fa-clone"></i>
                                            </a>
                                            <input type="hidden" value="{{ $deposit->address }}"
                                                id="deposit{{ $deposit->address }}" class="form-control text-left"
                                                placeholder="آدرس واریز" aria-label="Username" readonly>
                                            <a href="javascript:void(0);">
                                                <small>{{ shorten_hash($deposit->address) }}</small>
                                            </a>
                                        @else
                                            <span>N/A Address</span>
                                        @endif
                                    </h6>
                                </td>

                                <td class="font-number">

                                    <h6 class="mb-0">
                                        @if ($deposit->explorer_tx_url && $deposit->transaction_hash)
                                            <a href="javascript:void(0);" class="clipboard-btn mx-1"
                                                data-clipboard-action="copy"
                                                data-clipboard-target="#deposit{{ $deposit->transaction_hash }}">
                                                <i class="fa-regular fa-clone"></i>
                                            </a>
                                            <input type="hidden" value="{{ $deposit->transaction_hash }}"
                                                id="deposit{{ $deposit->transaction_hash }}"
                                                class="form-control text-left" placeholder="هش تراکنش"
                                                aria-label="هش تراکنش" readonly>
                                            <a href="{{ $deposit->explorer_tx_url }}" target="_blank">
                                                <small>{{ shorten_hash($deposit->transaction_hash) }}</small>
                                            </a>
                                        @elseif($deposit->transaction_hash)
                                            <a href="javascript:void(0);" class="clipboard-btn mx-1"
                                                data-clipboard-action="copy"
                                                data-clipboard-target="#deposit{{ $deposit->transaction_hash }}">
                                                <i class="fa-regular fa-clone"></i>
                                            </a>
                                            <input type="hidden" value="{{ $deposit->transaction_hash }}"
                                                id="deposit{{ $deposit->transaction_hash }}"
                                                class="form-control text-left" placeholder="هش تراکنش"
                                                aria-label="هش تراکنش" readonly>
                                            <a href="{{ $deposit->explorer_tx_url }}" target="_blank">
                                                <small>{{ shorten_hash($deposit->transaction_hash) }}</small>
                                            </a>
                                        @else
                                            <span>N/A TxID</span>
                                        @endif
                                    </h6>

                                </td>
                                <td class="font-number">
                                    {{ \App\Helpers\DateFormatter::convertToPersianDate($deposit->created_at, 'H:i:s %Y/%m/%d') }}
                                </td>
                                <td class="font-number">
                                    {{ \App\Helpers\DateFormatter::convertToPersianDate($deposit->confirmed_at, 'H:i:s %Y/%m/%d') }}
                                </td>
                                <td>
                                    <span
                                        class="badge bg-label-{{ $deposit->status->color() }}">{{ $deposit->status->label() }}</span>
                                </td>
                                <td>
                                    @if ($deposit->status === \App\Enums\DepositStatusEnum::CONFIRMED)
                                        <a href="" class="btn btn-icon btn-text-secondary" data-bs-toggle="modal"
                                            data-bs-target="#deposit-{{ $deposit->id }}">
                                            <i class="fa-regular fa-eye fa-xl"></i>
                                        </a>
                                        @include('dashboard.deposits.deposit-detail-modal', [
                                            'deposit' => $deposit,
                                        ])
                                    @elseif($deposit->status === \App\Enums\DepositStatusEnum::TOO_SMALL)
                                        <form action="{{ route('admin.deposit.approve', $deposit->id) }}" method="POST" 
                                            onsubmit="return confirm('آیا از تایید این واریزی و افزودن به حساب کاربر اطمینان دارید؟');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="تایید و افزودن به حساب">
                                                <i class="fa-solid fa-check me-1"></i> تایید
                                            </button>
                                        </form>
                                    @endif


                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        <div class="row mt-4">
            <div class="col-md-12">
                {{ $deposits->appends(request()->all())->links() }}
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
                window.location.href = "{{ route('admin.deposit.index') }}";
            });
        });
    </script>
@endsection
