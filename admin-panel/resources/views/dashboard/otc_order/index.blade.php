@extends('dashboard.layout.master')
@section('title', 'مدیریت بازارهای OTC')
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
   <style>
       .table-responsive {
            overflow-x: auto;
            position: relative;
        }

        .table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .sticky-column {
            position: sticky;
            left: 0;
            background-color: #fff !important;
            z-index: 1;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1) !important;
        }

        .table thead .sticky-column {
            z-index: 3 !important;
            background-color: #fff !important;
        }

        .table tbody tr .sticky-column {
            background-color: #fff !important;
        }

        .table tbody tr:hover .sticky-column {
            background-color: #f8f9fa !important;
        }

        /* Support for colored rows */
        .table tbody tr.table-success .sticky-column {
            background-color: #d1e7dd !important;
        }

        .table tbody tr.table-danger .sticky-column {
            background-color: #f8d7da !important;
        }

        .table tbody tr.table-warning .sticky-column {
            background-color: #fff3cd !important;
        }

        .table tbody tr.table-info .sticky-column {
            background-color: #cff4fc !important;
        }

        .table tbody tr.table-primary .sticky-column {
            background-color: #cfe2ff !important;
        }
    </style>
@endsection
@section('content')
                        <div class="row g-4 mb-4">
                            <div class="col-sm-12 col-lg-4 col-xxl-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div class="content-left"><span>تعداد معاملات</span>
                                                <div class="d-flex align-items-center my-1">
                                                    <h4 class="mb-0 me-2">{{ $otcOrders->total() }}</h4>
                                                </div>
                                            </div>
                                            <span class="badge bg-label-success rounded p-2">
                                                <i class="fa-light fa-swap fa-lg"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-lg-4 col-xxl-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div class="content-left">
                                                <span>تعداد معاملات امروز</span>
                                                <div class="d-flex align-items-center my-1">
                                                    <h4 class="mb-0 me-2">{{ $todayOrderCount }}</h4>
                                                </div>
                                            </div>
                                            <span class="badge bg-label-warning rounded">
                                                <i class="fa-light fa-swap"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-lg-4 col-xxl-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div class="content-left">
                                                <span>تعداد معاملات خرید</span>
                                                <div class="d-flex align-items-center my-1">
                                                    <h4 class="mb-0 me-2">{{ $totalBuyOrderCount }}</h4>
                                                </div>
                                            </div>
                                            <span class="badge bg-label-primary rounded p-2">
                                                <i class="fa-regular fa-user-tag"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-lg-4 col-xxl-3">
                                <div class="card">
                                    <div class="card-body ">
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div class="content-left">
                                                <span>تعداد معاملات فروش</span>
                                                <div class="d-flex align-items-center my-1">
                                                    <h4 class="mb-0 me-2">{{ $totalSellOrderCount }}</h4>
                                                </div>
                                            </div>
                                            <span class="badge bg-label-primary rounded p-2">
                                                <i class="fa-regular fa-user-tag"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-lg-4 col-xxl-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div class="content-left">
                                                <span>حجم معاملات</span>
                                                <div class="d-flex align-items-center my-1">
                                                    <h4 class="mb-0 me-2">{{ formatNumber($totalOrdersValue) }}
                                                        <small>USDT</small>
                                                    </h4>
                                                </div>
                                            </div>
                                            <span class="badge bg-label-primary rounded p-2">
                                                <i class="fa-regular fa-user-tag"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-lg-4 col-xxl-3">
                                <div class="card">
                                    <div class="card-body bg-success">
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div class="content-left">
                                                <span class="text-white">کاربران با بیشترین معامله امروز</span>
                                                <div class="d-flex align-items-baseline my-1">
                                                    <small class="text-white mx-2">حجم معاملات امروز:</small>
                                                    <h4 class="mb-0 me-2 text-primary">{{ formatNumber($totalTodayOrdersValue, 2) }}</h4>
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
                                                                        <span class='fw-medium font-monospace'>{{ formatNumberTrimZeros($topUser['totalOrders']) }}$</span>
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
                                                    بدون معامله
                                                @endif

                                            </ul>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="card-title header-elements">
                                    <h5 class="m-0 me-2">فیلتر پیشرفته معاملات OTC</h5>
                                    <div class="card-title-elements ms-auto">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="toggleAdvancedFilter">
                                            <i class="fas fa-chevron-down me-1"></i> نمایش فیلترهای پیشرفته
                                        </button>
                                    </div>
                                </div>
                                <form action="{{ route('admin.otc_orders.index') }}" method="get" id="filterForm">

                                    <!-- Basic Filters Row -->
                                    <div class="row mb-3">
                                        <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                            <label class="form-label" for="market">بازار:</label>
                                            <select name="market" class="form-select" id="market">
                                                <option value="">همه بازارها</option>
                                                @if (isset($markets))
                                                    @foreach ($markets as $market)
                                                        <option value="{{ $market->id }}" {{ request()->input('market') == $market->id ? 'selected' : '' }}>
                                                            {{ $market->base_currency }}/{{ $market->quote_currency }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                        <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                            <label class="form-label" for="type">نوع معامله:</label>
                                            <select name="type" class="form-control" id="type">
                                                <option value="">همه</option>
                                                @foreach (\App\Enums\OTCOrderTypeEnum::cases() as $case)
                                                    <option value="{{ $case->value }}"
                                                        {{ request()->input('type') === $case->value ? 'selected' : '' }}>
                                                        {{ $case->label() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                            <label class="form-label" for="status">وضعیت:</label>
                                            <select name="status" class="form-control" id="status">
                                                <option value="">همه</option>
                                                @foreach (\App\Enums\OTCOrderStatusEnum::cases() as $case)
                                                    <option value="{{ $case->value }}"
                                                        {{ request()->input('status') === $case->value ? 'selected' : '' }}>
                                                        {{ $case->label() }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Second Row: User and Buttons -->
                                    <div class="row mb-3">
                                        <div class="col-lg-6 col-md-8 col-sm-12 mb-2">
                                            <label class="form-label" for="user">کاربر:</label>
                                            <x-user-selection-component input-name="user" multiple="0"
                                                selected="{{ request()->filled('user') && $otcOrders->isNotEmpty() && $otcOrders->first()?->user ? $otcOrders->first()->user->id : '' }}"
                                                selected-label="{{ request()->filled('user') && $otcOrders->isNotEmpty() && $otcOrders->first()?->user
        ? '(' . $otcOrders->first()->user->id . '#) ' . $otcOrders->first()->user->fullname() . ' | ' . $otcOrders->first()->user->email
        : '' }}"></x-user-selection-component>
                                        </div>
                                        <div class="col-lg-6 col-md-4 col-sm-12 mb-2 d-flex align-items-end pb-2">
                                            <div class="d-flex flex-wrap gap-1">
                                                <button class="btn btn-success btn-sm" type="submit">
                                                    <i class="fas fa-filter me-1"></i> اعمال فیلتر
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm" id="clearFilters">
                                                    <i class="fas fa-times me-1"></i> حذف فیلترها
                                                </button>
                                                <button type="button" class="btn btn-outline-info btn-sm" id="exportFiltered">
                                                    <i class="fas fa-file-excel me-1"></i> خروجی اکسل
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Advanced Filters Row (Initially Hidden) -->
                                    <div class="row mb-3" id="advancedFilters" style="display: none;">
                                        <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                                            <label class="form-label" for="date_from">از تاریخ:</label>
                                            <input type="text" name="date_from" class="form-control" id="date_from" data-jdp
                                                value="{{ request()->input('date_from') }}" placeholder="1403/01/01" autocomplete="off">
                                        </div>
                                        <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                                            <label class="form-label" for="date_to">تا تاریخ:</label>
                                            <input type="text" name="date_to" class="form-control" id="date_to" data-jdp
                                                value="{{ request()->input('date_to') }}" placeholder="1403/12/29" autocomplete="off">
                                        </div>
                                        <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                                            <label class="form-label" for="quantity_min">مقدار (از):</label>
                                            <input type="number" name="quantity_min" class="form-control" id="quantity_min"
                                                step="any" value="{{ request()->input('quantity_min') }}" placeholder="0">
                                        </div>
                                        <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                                            <label class="form-label" for="quantity_max">مقدار (تا):</label>
                                            <input type="number" name="quantity_max" class="form-control" id="quantity_max"
                                                step="any" value="{{ request()->input('quantity_max') }}" placeholder="0">
                                        </div>
                                        <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                                            <label class="form-label" for="total_value_min">مبلغ کل (از) USDT:</label>
                                            <input type="number" name="total_value_min" class="form-control" id="total_value_min"
                                                step="any" value="{{ request()->input('total_value_min') }}" placeholder="0">
                                        </div>
                                        <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                                            <label class="form-label" for="total_value_max">مبلغ کل (تا) USDT:</label>
                                            <input type="number" name="total_value_max" class="form-control" id="total_value_max"
                                                step="any" value="{{ request()->input('total_value_max') }}" placeholder="0">
                                        </div>
                                    </div>

                                    <!-- Active Filter Summary -->
                                    @if (request()->hasAny(['market', 'type', 'status', 'user', 'date_from', 'date_to', 'quantity_min', 'quantity_max', 'total_value_min', 'total_value_max']))
                                        <div class="alert alert-info d-flex align-items-center">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <span class="me-2">فیلترهای فعال:</span>
                                            <div class="d-flex flex-wrap gap-1">
                                                @if (request()->filled('market') && isset($markets))
                                                    @php $selectedMarket = $markets->find(request()->input('market')); @endphp
                                                    <span class="badge bg-primary">بازار: {{ $selectedMarket ? $selectedMarket->base_currency . '/' . $selectedMarket->quote_currency : request()->input('market') }}</span>
                                                @endif
                                                @if (request()->filled('type'))
                                                    @php $selectedType = \App\Enums\OTCOrderTypeEnum::tryFrom(request()->input('type')); @endphp
                                                    <span class="badge bg-primary">نوع: {{ $selectedType?->label() ?? request()->input('type') }}</span>
                                                @endif
                                                @if (request()->filled('status'))
                                                    @php $selectedStatus = \App\Enums\OTCOrderStatusEnum::tryFrom(request()->input('status')); @endphp
                                                    <span class="badge bg-primary">وضعیت: {{ $selectedStatus?->label() ?? request()->input('status') }}</span>
                                                @endif
                                                @if (request()->filled('user'))
                                                    @php $selectedUser = \App\Models\User::find(request()->input('user')); @endphp
                                                    @if ($selectedUser)
                                                        <span class="badge bg-primary">کاربر: (#{{ $selectedUser->id }}) {{ $selectedUser->fullname() }}</span>
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
                                                @if (request()->filled('quantity_min'))
                                                    <span class="badge bg-warning">مقدار ≥ {{ request()->input('quantity_min') }}</span>
                                                @endif
                                                @if (request()->filled('quantity_max'))
                                                    <span class="badge bg-warning">مقدار ≤ {{ request()->input('quantity_max') }}</span>
                                                @endif
                                                @if (request()->filled('total_value_min'))
                                                    <span class="badge bg-success">مبلغ کل ≥ {{ request()->input('total_value_min') }}</span>
                                                @endif
                                                @if (request()->filled('total_value_max'))
                                                    <span class="badge bg-success">مبلغ کل ≤ {{ request()->input('total_value_max') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </form>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <div class="card-title header-elements">
                                    <h5 class="m-0 me-2">لیست معاملات OTC</h5>

                                </div>
                            </div>
                            <div class="table-responsive text-nowrap">
                                <table class="table ">
                                    <thead>
                                        <tr>
                                            <th>
                                                @php
    $currentParams = request()->except('sortById');
    $currentSortDirection = request()->input('sortById', 'desc');
    $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                                                @endphp
                                                <a href="{{ route('admin.otc_orders.index', array_merge($currentParams, ['sortById' => $newSortDirection])) }}"
                                                    class="text-black">
                                                    ID
                                                    @if ($currentSortDirection === 'asc')
                                                        <span><i class="fa-solid fa-arrow-up"></i></span>
                                                    @else
                                                        <span><i class="fa-solid fa-arrow-down"></i></span>
                                                    @endif
                                                </a>
                                            </th>
                                            <th>بازار</th>
                                            <th>نوع</th>
                                            <th>کاربر</th>
                                            <th>
                                                @php
    $currentParams = request()->except('sortByQuantity');
    $currentSortDirection = request()->input('sortByQuantity', 'desc');
    $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                                                @endphp
                                                <a href="{{ route('admin.otc_orders.index', array_merge($currentParams, ['sortByQuantity' => $newSortDirection])) }}"
                                                    class="text-black">
                                                    مقدار
                                                    @if ($currentSortDirection === 'asc')
                                                        <span><i class="fa-solid fa-arrow-up"></i></span>
                                                    @else
                                                        <span><i class="fa-solid fa-arrow-down"></i></span>
                                                    @endif
                                                </a>
                                            </th>
                                            <th>قیمت واحد</th>
                                            <th>
                                                @php
    $currentParams = request()->except('sortByTotalValue');
    $currentSortDirection = request()->input('sortByTotalValue', 'desc');
    $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                                                @endphp
                                                <a href="{{ route('admin.otc_orders.index', array_merge($currentParams, ['sortByTotalValue' => $newSortDirection])) }}"
                                                    class="text-black">
                                                    مبلغ کل
                                                    @if ($currentSortDirection === 'asc')
                                                        <span><i class="fa-solid fa-arrow-up"></i></span>
                                                    @else
                                                        <span><i class="fa-solid fa-arrow-down"></i></span>
                                                    @endif
                                                </a>
                                            </th>
                                            <th>کارمزد</th>
                                            <th>دریافتی</th>
                                            <th>
                                                @php
    $currentParams = request()->except('sortByCreatedAt');
    $currentSortDirection = request()->input('sortByCreatedAt', 'desc');
    $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                                                @endphp
                                                <a href="{{ route('admin.otc_orders.index', array_merge($currentParams, ['sortByCreatedAt' => $newSortDirection])) }}"
                                                    class="text-black">
                                                    تاریخ و زمان
                                                    @if ($currentSortDirection === 'asc')
                                                        <span><i class="fa-solid fa-arrow-up"></i></span>
                                                    @else
                                                        <span><i class="fa-solid fa-arrow-down"></i></span>
                                                    @endif
                                                </a>

                                            </th>
                                            <th>وضعیت</th>
                                            <th class="sticky-column">عملیات</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        @if ($otcOrders->isEmpty())
                                            <tr>
                                                <td colspan="9" class="text-center">تراکنشی یافت نشد.</td>
                                            </tr>
                                        @else
                                            @foreach ($otcOrders as $order)

                                                <tr class="table-{{ $order->status->color() }}">
                                                    <td>{{ $order->id }}</td>
                                                    <td class="text-heading fw-medium">
                                                        <img src="{{ asset($order->market->baseCurrency->coinLogo()) }}"
                                                            class="rounded-circle" width="32px">
                                                        <small> {{ $order->market->name }}</small>

                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge bg-label-{{ $order->type->color() }}">{{ $order->type->label() }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <a href="" class="text-heading text-truncate">
                                                                <small class="fw-medium">{{ $order->user->email }}</small>
                                                            </a>
                                                            <small>{{ $order->user->username }}</small>
                                                        </div>
                                                    </td>

                                                    <td class="font-number" dir="ltr">
                                                        <span class="ms-2">{{ formatNumberTrimZeros($order->quantity) }}</span>
                                                        <small>{{ $order->market->baseCurrency->symbol }}</small>
                                                    </td>
                                                    <td class="font-number" dir="ltr">
                                                        <span class="ms-2">{{ formatNumberTrimZeros($order->price) }}</span>
                                                        <small>{{ $order->market->quoteCurrency->symbol }}</small>
                                                    </td>
                                                    <td class="font-number" dir="ltr">
                                                        {{ formatNumberTrimZeros($order->total_value) }}
                                                        <small>USDT</small>
                                                    </td>
                                                    <td class="font-number" dir="ltr">
                                                        {{ formatNumberTrimZeros($order->fee) }}

                                                        <small>{{ $order->type === \App\Enums\OTCOrderTypeEnum::BUY ? $order->market->baseCurrency->symbol : $order->market->quoteCurrency->symbol }}</small>
                                                    </td>
                                                    <td class="font-number" dir="ltr">
                                                        @if ($order->type === \App\Enums\OTCOrderTypeEnum::BUY)
                                                            {{ formatNumberTrimZeros(bcsub($order->quantity, $order->fee, 8)) }}
                                                            <small>{{ $order->market->baseCurrency->symbol }}</small>
                                                        @else
                                                            {{ formatNumberTrimZeros(bcsub(bcmul($order->price, $order->quantity, 5), $order->fee, 5)) }}
                                                            <small>{{ $order->market->quoteCurrency->symbol }}</small>
                                                        @endif
                                                    </td>
                                                    <td class="font-number">
                                                        {{ \App\Helpers\DateFormatter::convertToPersianDate($order->created_at, 'H:i:s %Y/%m/%d') }}
                                                    </td>

                                                    <td>
                                                        <span
                                                            class="badge bg-{{ $order->status->color() }}">{{ $order->status->label() }}</span>
                                                        @if ($order->ref_exchange_sell_status && $order->ref_exchange_sell_status !== \App\Enums\RefExchangeSellStatusEnum::NOT_REQUIRED)
                                                            <br>
                                                            <small class="badge bg-label-{{ $order->ref_exchange_sell_status->color() }} mt-1">
                                                                صرافی مرجع: {{ $order->ref_exchange_sell_status->label() }}
                                                            </small>
                                                        @endif
                                                    </td>
                                                    <td class="sticky-column">
                                                         @if ($order->status !== \App\Enums\OTCOrderStatusEnum::CANCELED)
                                                        <a href="" class="btn btn-icon btn-text-secondary" data-bs-toggle="modal"
                                                            data-bs-target="#otc-{{ $order->id }}">
                                                            <i class="fa-light fa-memo-circle-info fa-lg"></i>
                                                        </a>
                                                        @endif
                                                        @if ($order->status === \App\Enums\OTCOrderStatusEnum::CANCELED)
                                                            <a href="#" class="btn btn-icon btn-text-secondary" data-bs-toggle="modal"
                                                                data-bs-target="#otc-description-{{ $order->id }}">
                                                                <i class="fa-regular fa-eye fa-lg"></i>
                                                            </a>
                                                        @endif

                                                        {{-- Trigger Reference Exchange Sell Button --}}
                                                        @if ($order->ref_exchange_sell_status === \App\Enums\RefExchangeSellStatusEnum::PENDING)
                                                            <button type="button"
                                                                class="btn btn-icon btn-text-warning trigger-ref-exchange-sell"
                                                                data-order-id="{{ $order->id }}"
                                                                data-bs-toggle="tooltip"
                                                                title="تکمیل فروش در صرافی مرجع">
                                                                <i class="fa-solid fa-arrow-right-arrow-left fa-lg"></i>
                                                            </button>
                                                        @endif

                                                        {{-- Reset Failed Status Button --}}
                                                        @if ($order->ref_exchange_sell_status === \App\Enums\RefExchangeSellStatusEnum::FAILED)
                                                            <button type="button"
                                                                class="btn btn-icon btn-text-danger reset-ref-exchange-sell"
                                                                data-order-id="{{ $order->id }}"
                                                                data-bs-toggle="tooltip"
                                                                title="ریست و تلاش مجدد">
                                                                <i class="fa-solid fa-rotate-right fa-lg"></i>
                                                            </button>
                                                        @endif

                                                        @if(auth()->user()->hasRole('tech_developers'))
                                                        <button type="button" class="btn btn-icon btn-text-warning"
                                                            data-bs-toggle="modal" data-bs-target="#note-otc-{{ $order->id }}"
                                                            title="ثبت نوت">
                                                            <i class="{{ $order->notes ? 'fa-solid' : 'fa-regular' }} fa-note-sticky fa-lg {{ $order->notes ? 'text-warning' : '' }}"></i>
                                                        </button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            {{-- Description Modals --}}
                            @foreach ($otcOrders as $order)
                                @include('dashboard.otc_order.otc-description-modal', [
            'order' => $order,
        ])
                            @endforeach

                            {{-- Transaction Modals --}}
                            @foreach ($otcOrders as $order)
                                <x-transaction-modal modal-id="otc-{{ $order->id }}"
                                    title="تراکنش های معامله #{{ $order->id }}" :user="$order->user" :transactions="$order->transactions"
                                    route-name="admin.transaction.index" route-param="otc_order_id"
                                    :route-param-value="$order->id"
                                    :ref-exchange-description="$order->ref_exchange_description" />
                            @endforeach

                            {{-- Developer Notes Modals --}}
                            @if(auth()->user()->hasRole('tech_developers'))
                            @foreach ($otcOrders as $order)
                            <div class="modal fade" id="note-otc-{{ $order->id }}" tabindex="-1" aria-modal="true" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">نوت معامله OTC #{{ $order->id }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <textarea class="form-control otc-notes-input" rows="5"
                                                placeholder="نوت خود را اینجا بنویسید..."
                                                data-id="{{ $order->id }}"
                                                data-url="{{ route('admin.otc_orders.notes.update', $order->id) }}">{{ $order->notes }}</textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                                            <button type="button" class="btn btn-primary save-otc-note"
                                                data-id="{{ $order->id }}">ذخیره</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            @endif
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    {{ $otcOrders->appends(request()->all())->links() }}
                                </div>
                            </div>
                        </div>

@endsection

@section('vendor-script')
    @vite(['resources/assets/js/jalalidatepicker.js'])
    {{-- SweetAlert2 --}}
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    <script>
        $(document).ready(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();

            // Toggle Advanced Filters
            $('#toggleAdvancedFilter').click(function () {
                const advancedFilters = $('#advancedFilters');
                const button = $(this);

                if (advancedFilters.is(':visible')) {
                    advancedFilters.slideUp();
                    button.html('<i class="fas fa-chevron-down me-1"></i> نمایش فیلترهای پیشرفته');
                } else {
                    advancedFilters.slideDown();
                    button.html('<i class="fas fa-chevron-up me-1"></i> مخفی کردن فیلترهای پیشرفته');
                }
            });

            // Clear Filters Button
            $('#clearFilters').click(function () {
                window.location.href = '{{ route('admin.otc_orders.index') }}';
            });

            // Export to Excel Button
            $('#exportFiltered').click(function () {
                const button = $(this);
                const originalText = button.html();

                button.prop('disabled', true);
                button.html('<i class="fas fa-spinner fa-spin me-1"></i>در حال تولید...');

                const formData = $('#filterForm').serialize();
                const exportUrl = '{{ route('admin.otc_orders.excel-export') }}?' + formData;

                const link = document.createElement('a');
                link.href = exportUrl;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                setTimeout(function () {
                    button.prop('disabled', false);
                    button.html(originalText);
                }, 2000);
            });

            // Auto-show advanced filters if any advanced input has a value
            const advancedInputs = ['date_from', 'date_to', 'quantity_min', 'quantity_max', 'total_value_min', 'total_value_max'];
            let hasAdvancedValue = false;
            advancedInputs.forEach(function (inputName) {
                if ($('input[name="' + inputName + '"]').val()) {
                    hasAdvancedValue = true;
                }
            });
            if (hasAdvancedValue) {
                $('#advancedFilters').show();
                $('#toggleAdvancedFilter').html('<i class="fas fa-chevron-up me-1"></i> مخفی کردن فیلترهای پیشرفته');
            }

            // Save OTC order note
            $(document).on('click', '.save-otc-note', function () {
                const id = $(this).data('id');
                const textarea = $('.otc-notes-input[data-id="' + id + '"]');
                const url = textarea.data('url');
                const notes = textarea.val();
                const btn = $(this);

                btn.prop('disabled', true);
                $.ajax({
                    url: url,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: { notes: notes },
                    success: function (res) {
                        Toastify({
                            text: res.message,
                            duration: 3000,
                            gravity: 'top', position: 'right',
                            style: { background: '#28C76F' }
                        }).showToast();
                        $('#note-otc-' + id).modal('hide');
                        const noteBtn = $('[data-bs-target="#note-otc-' + id + '"] i');
                        if (notes.trim()) {
                            noteBtn.addClass('text-warning fa-solid').removeClass('fa-regular');
                        } else {
                            noteBtn.removeClass('text-warning fa-solid').addClass('fa-regular');
                        }
                    },
                    error: function () {
                        Toastify({
                            text: 'خطا در ذخیره نوت',
                            duration: 5000,
                            gravity: 'top', position: 'right',
                            style: { background: '#EA5455' }
                        }).showToast();
                    },
                    complete: function () { btn.prop('disabled', false); }
                });
            });

            // Trigger Reference Exchange Sell
            $('.trigger-ref-exchange-sell').on('click', function() {
                const button = $(this);
                const orderId = button.data('order-id');

                Swal.fire({
                    title: 'تکمیل فروش در صرافی مرجع',
                    text: 'آیا از تکمیل فروش در صرافی مرجع اطمینان دارید؟',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#696cff',
                    cancelButtonColor: '#8592a3',
                    confirmButtonText: 'بله، انجام بده',
                    cancelButtonText: 'انصراف',
                    customClass: {
                        confirmButton: 'btn btn-primary me-2',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        button.prop('disabled', true);
                        button.find('i').removeClass('fa-arrow-right-arrow-left').addClass('fa-spinner fa-spin');

                        $.ajax({
                            url: '{{ route("admin.otc_orders.index") }}/' + orderId + '/trigger-ref-exchange-sell',
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'موفق!',
                                        text: response.message,
                                        icon: 'success',
                                        confirmButtonText: 'باشه',
                                        confirmButtonColor: '#696cff',
                                        customClass: {
                                            confirmButton: 'btn btn-primary'
                                        },
                                        buttonsStyling: false
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'خطا!',
                                        text: response.message || 'خطا در تکمیل فروش',
                                        icon: 'error',
                                        confirmButtonText: 'باشه',
                                        confirmButtonColor: '#696cff',
                                        customClass: {
                                            confirmButton: 'btn btn-primary'
                                        },
                                        buttonsStyling: false
                                    });
                                    location.reload();
                                    button.prop('disabled', false);
                                    button.find('i').removeClass('fa-spinner fa-spin').addClass('fa-arrow-right-arrow-left');
                                }
                            },
                            error: function(xhr) {
                                const message = xhr.responseJSON?.message || 'خطا در ارتباط با سرور';
                                Swal.fire({
                                    title: 'خطا!',
                                    text: message,
                                    icon: 'error',
                                    confirmButtonText: 'باشه',
                                    confirmButtonColor: '#696cff',
                                    customClass: {
                                        confirmButton: 'btn btn-primary'
                                    },
                                    buttonsStyling: false
                                });
                                location.reload();
                                button.prop('disabled', false);
                                button.find('i').removeClass('fa-spinner fa-spin').addClass('fa-arrow-right-arrow-left');
                            }
                        });
                    }
                });
            });

            // Reset Failed Reference Exchange Sell Status
            $('.reset-ref-exchange-sell').on('click', function() {
                const button = $(this);
                const orderId = button.data('order-id');

                Swal.fire({
                    title: 'ریست وضعیت',
                    text: 'آیا از ریست کردن وضعیت اطمینان دارید؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#696cff',
                    cancelButtonColor: '#8592a3',
                    confirmButtonText: 'بله، ریست کن',
                    cancelButtonText: 'انصراف',
                    customClass: {
                        confirmButton: 'btn btn-primary me-2',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        button.prop('disabled', true);
                        button.find('i').removeClass('fa-rotate-right').addClass('fa-spinner fa-spin');

                        $.ajax({
                            url: '{{ route("admin.otc_orders.index") }}/' + orderId + '/reset-ref-exchange-sell',
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'موفق!',
                                        text: response.message,
                                        icon: 'success',
                                        confirmButtonText: 'باشه',
                                        confirmButtonColor: '#696cff',
                                        customClass: {
                                            confirmButton: 'btn btn-primary'
                                        },
                                        buttonsStyling: false
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'خطا!',
                                        text: response.message || 'خطا در ریست وضعیت',
                                        icon: 'error',
                                        confirmButtonText: 'باشه',
                                        confirmButtonColor: '#696cff',
                                        customClass: {
                                            confirmButton: 'btn btn-primary'
                                        },
                                        buttonsStyling: false
                                    });
                                    button.prop('disabled', false);
                                    button.find('i').removeClass('fa-spinner fa-spin').addClass('fa-rotate-right');
                                }
                            },
                            error: function(xhr) {
                                const message = xhr.responseJSON?.message || 'خطا در ارتباط با سرور';
                                Swal.fire({
                                    title: 'خطا!',
                                    text: message,
                                    icon: 'error',
                                    confirmButtonText: 'باشه',
                                    confirmButtonColor: '#696cff',
                                    customClass: {
                                        confirmButton: 'btn btn-primary'
                                    },
                                    buttonsStyling: false
                                });
                                button.prop('disabled', false);
                                button.find('i').removeClass('fa-spinner fa-spin').addClass('fa-rotate-right');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
