@extends('dashboard.layout.master')
@section('title', 'مدیریت سفارشات Spot')
@section('content')
    {{-- TODO: Complete OTC ORder Card --}}

    <!-- Tab Navigation -->
    <div class="card mb-4">
        <div class="card-body p-3">
            <ul class="nav nav-pills nav-fill gap-2" id="orderTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link d-flex align-items-center justify-content-center gap-2 py-3 px-4 rounded-pill fw-semibold transition-all-3 {{ !request()->has('source') || request()->input('source') === 'user' ? 'active shadow-lg' : '' }}"
                        href="{{ route('admin.spot_orders.index', array_merge(request()->except('source'), ['source' => 'user'])) }}"
                        style="{{ !request()->has('source') || request()->input('source') === 'user' ? 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;' : 'background: #f8f9fa; color: #6c757d; border: 1px solid #dee2e6;' }}">
                        <i class="fa-light fa-user fs-5"></i>
                        <span class="fw-bold">سفارشات کاربران</span>
                        <span class="badge bg-white text-primary ms-2 px-2 py-1 rounded-pill"
                            style="font-size: 0.75rem; font-weight: 700;">
                            {{ formatNumberTrimZeros($userOrdersCount ?? 0) }}
                        </span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link d-flex align-items-center justify-content-center gap-2 py-3 px-4 rounded-pill fw-semibold transition-all-3 {{ request()->input('source') === 'bot' ? 'active shadow-lg' : '' }}"
                        href="{{ route('admin.spot_orders.index', array_merge(request()->except('source'), ['source' => 'bot'])) }}"
                        style="{{ request()->input('source') === 'bot' ? 'background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border: none;' : 'background: #f8f9fa; color: #6c757d; border: 1px solid #dee2e6;' }}">
                        <i class="fa-light fa-robot fs-5"></i>
                        <span class="fw-bold">سفارشات ربات معاملاتی</span>
                        <span class="badge bg-white text-danger ms-2 px-2 py-1 rounded-pill"
                            style="font-size: 0.75rem; font-weight: 700;">
                            {{ formatNumberTrimZeros($botOrdersCount ?? 0) }}
                        </span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">فیلتر پیشرفته سفارشات اسپات</h5>
                <div class="card-title-elements ms-auto">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="toggleAdvancedFilter">
                        <i class="fas fa-chevron-down me-1"></i>
                        نمایش فیلترهای پیشرفته
                    </button>
                </div>
            </div>
            <form action="{{ route('admin.spot_orders.index') }}" method="get" id="filterForm">
                <!-- Hidden input to maintain current source -->
                <input type="hidden" name="source" value="{{ request()->input('source', 'user') }}">

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
                        <label class="form-label" for="type">نوع سفارش:</label>
                        <select name="type" class="form-select" id="type">
                            <option value="">همه</option>
                            @foreach (\App\Enums\SpotOrderTypeEnum::cases() as $case)
                                <option value="{{ $case->name }}" {{ request()->has('type') && request()->input('type') == $case->name ? 'selected' : '' }}>
                                    {{ $case->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                        <label class="form-label" for="status">وضعیت سفارش:</label>
                        <select name="status" class="form-select" id="status">
                            <option value="">همه وضعیت‌ها</option>
                            @foreach (\App\Enums\SpotOrderStatusEnum::cases() as $case)
                                <option value="{{ $case->name }}" {{ request()->input('status') == $case->name ? 'selected' : '' }}>
                                    {{ $case->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-12 col-12">
                        <label class="form-label d-none d-lg-block">&nbsp;</label>
                        <div class="d-flex flex-wrap gap-1 justify-content-start">
                            <button class="btn btn-success btn-sm flex-fill" type="submit" style="min-width: 70px;">
                                <i class="fas fa-search me-1"></i>جستجو
                            </button>
                            <button class="btn btn-outline-secondary btn-sm flex-fill" type="button" id="clearFilters"
                                style="min-width: 70px;">
                                <i class="fas fa-times me-1"></i>پاک کردن
                            </button>
                            <button class="btn btn-outline-info btn-sm flex-fill" type="button" id="exportFiltered"
                                style="min-width: 60px;">
                                <i class="fas fa-download me-1"></i>اکسل
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Second Row for User and Date Filters -->
                <div class="row mb-3">
                    @if (request()->input('source', 'user') === 'user')
                                <div class="col-lg-6 col-md-6 col-sm-12 mb-2">
                                    <label class="form-label" for="user">کاربر:</label>
                                    <x-user-selection-component input-name="user" multiple="0"
                                        selected="{{ request()->filled('user') ? request()->input('user') : '' }}" selected-label="{{ request()->filled('user')
                        ? '(#' .
                        request()->input('user') .
                        ') ' .
                        \App\Models\User::find(request()->input('user'))?->fullname() .
                        ' - ' .
                        \App\Models\User::find(request()->input('user'))?->email ??
                        'کاربر #' . request()->input('user')
                        : '' }}"></x-user-selection-component>
                                </div>
                    @endif
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
                </div>

                <!-- Advanced Filters Row (Initially Hidden) -->
                <div class="row mb-3" id="advancedFilters" style="display: none;">
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                        <label class="form-label" for="quantity_min">حداقل مقدار:</label>
                        <input type="number" name="quantity_min" class="form-control" id="quantity_min" placeholder="0.00"
                            step="0.00000001" value="{{ request()->input('quantity_min') }}">
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                        <label class="form-label" for="quantity_max">حداکثر مقدار:</label>
                        <input type="number" name="quantity_max" class="form-control" id="quantity_max" placeholder="0.00"
                            step="0.00000001" value="{{ request()->input('quantity_max') }}">
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                        <label class="form-label" for="price_min">حداقل قیمت:</label>
                        <div class="input-group">
                            <input type="number" name="price_min" class="form-control" id="price_min" placeholder="0.00"
                                step="0.01" value="{{ request()->input('price_min') }}">
                            <span class="input-group-text">USDT</span>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 mb-2">
                        <label class="form-label" for="price_max">حداکثر قیمت:</label>
                        <div class="input-group">
                            <input type="number" name="price_max" class="form-control" id="price_max" placeholder="0.00"
                                step="0.01" value="{{ request()->input('price_max') }}">
                            <span class="input-group-text">USDT</span>
                        </div>
                    </div>
                </div>

                <!-- Filter Summary (Show active filters) -->
                @if (
                        request()->hasAny([
                            'market',
                            'type',
                            'status',
                            'user',
                            'date_from',
                            'date_to',
                            'price_min',
                            'price_max',
                            'quantity_min',
                            'quantity_max',
                        ])
                    )
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-info d-flex align-items-center">
                                <i class="fas fa-info-circle me-2"></i>
                                <span class="me-2">فیلترهای فعال:</span>
                                <div class="d-flex flex-wrap gap-1">
                                    @if (request()->filled('market') && isset($markets))
                                        @php $selectedMarket = $markets->find(request()->input('market')) @endphp
                                        <span class="badge bg-primary">بازار:
                                            {{ $selectedMarket ? $selectedMarket->base_currency . '/' . $selectedMarket->quote_currency : request()->input('market') }}</span>
                                    @endif
                                    @if (request()->filled('type'))
                                        @php
                                            $selectedType = \App\Enums\SpotOrderTypeEnum::tryFrom(
                                                request()->input('type'),
                                            );
                                        @endphp
                                        <span class="badge bg-primary">نوع:
                                            {{ $selectedType?->label() ?? request()->input('type') }}</span>
                                    @endif
                                    @if (request()->filled('status'))
                                        @php
                                            $selectedStatus = \App\Enums\SpotOrderStatusEnum::tryFrom(
                                                request()->input('status'),
                                            );
                                        @endphp
                                        <span class="badge bg-primary">وضعیت:
                                            {{ $selectedStatus?->label() ?? request()->input('status') }}</span>
                                    @endif
                                    @if (request()->filled('user'))
                                        @php
                                            $selectedUser = \App\Models\User::find(request()->input('user'));
                                        @endphp
                                        @if ($selectedUser)
                                            <span class="badge bg-primary">کاربر: (#{{ $selectedUser->id }})
                                                {{ $selectedUser->fullname() }} - {{ $selectedUser->email }}</span>
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
                                    @if (request()->filled('price_min'))
                                        <span class="badge bg-success">قیمت ≥ {{ request()->input('price_min') }}</span>
                                    @endif
                                    @if (request()->filled('price_max'))
                                        <span class="badge bg-success">قیمت ≤ {{ request()->input('price_max') }}</span>
                                    @endif
                                    @if (request()->filled('quantity_min'))
                                        <span class="badge bg-warning">مقدار ≥
                                            {{ request()->input('quantity_min') }}</span>
                                    @endif
                                    @if (request()->filled('quantity_max'))
                                        <span class="badge bg-warning">مقدار ≤
                                            {{ request()->input('quantity_max') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">
                    @if (request()->input('source', 'user') === 'bot')
                        لیست سفارشات ربات معاملاتی
                    @else
                        لیست سفارشات کاربران
                    @endif
                </h5>
                @if (request()->input('source', 'user') === 'user')
                    <div class="card-title-elements ms-auto">
                        <button type="button" class="btn btn-sm btn-danger" id="cancelAllOpenOrders">
                            <i class="fas fa-times-circle me-1"></i>
                            لغو تمام سفارشات باز
                        </button>
                    </div>
                @endif
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th class="text-nowrap">
                            @php
                                $currentParams = request()->except([
                                    'sortById',
                                    'sortByQuantity',
                                    'sortByPrice',
                                    'sortByFilledQuantity',
                                    'sortByCreatedAt',
                                ]);

                                $currentSortDirection = request()->input('sortById', 'desc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.spot_orders.index', array_merge($currentParams, ['sortById' => $newSortDirection])) }}"
                                class="text-black">
                                ID
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>
                        </th>
                        <th class="text-nowrap">بازار</th>
                        <th class="text-nowrap">سمت</th>
                        <th class="text-nowrap">نوع</th>
                        <th class="text-nowrap">
                            @php
                                $currentParams = request()->except([
                                    'sortById',
                                    'sortByQuantity',
                                    'sortByPrice',
                                    'sortByFilledQuantity',
                                    'sortByCreatedAt',
                                ]);
                                $currentSortDirection = request()->input('sortByQuantity', 'desc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.spot_orders.index', array_merge($currentParams, ['sortByQuantity' => $newSortDirection])) }}"
                                class="text-black">
                                مقدار
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>
                        </th>
                        <th class="text-nowrap">
                            @php
                                $currentParams = request()->except([
                                    'sortById',
                                    'sortByQuantity',
                                    'sortByPrice',
                                    'sortByFilledQuantity',
                                    'sortByCreatedAt',
                                ]);
                                $currentSortDirection = request()->input('sortByPrice', 'desc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.spot_orders.index', array_merge($currentParams, ['sortByPrice' => $newSortDirection])) }}"
                                class="text-black">
                                قیمت سفارش (USDT)
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>
                        </th>
                        @if (request()->input('source', 'user') === 'user')
                            <th class="text-nowrap">کاربر</th>
                        @else
                            <th class="text-nowrap">منبع</th>
                        @endif
                        <th class="text-nowrap">
                            @php
                                $currentParams = request()->except([
                                    'sortById',
                                    'sortByQuantity',
                                    'sortByPrice',
                                    'sortByFilledQuantity',
                                    'sortByCreatedAt',
                                ]);
                                $currentSortDirection = request()->input('sortByFilledQuantity', 'desc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.spot_orders.index', array_merge($currentParams, ['sortByFilledQuantity' => $newSortDirection])) }}"
                                class="text-black">
                                مقدار اجرا شده
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>
                        </th>
                        <th class="text-nowrap">
                            @php
                                $currentParams = request()->except([
                                    'sortById',
                                    'sortByQuantity',
                                    'sortByPrice',
                                    'sortByFilledQuantity',
                                    'sortByCreatedAt',
                                ]);
                                $currentSortDirection = request()->input('sortByCreatedAt', 'desc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.spot_orders.index', array_merge($currentParams, ['sortByCreatedAt' => $newSortDirection])) }}"
                                class="text-black">
                                تاریخ
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>
                        </th>
                        <th class="text-nowrap">وضعیت سفارش</th>
                        <th class="text-nowrap">جزییات</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @if ($spotOrders->isEmpty())
                        <tr>
                            <td colspan="14" class="text-center">سفارشی یافت نشد.</td>
                        </tr>
                    @else
                        @foreach ($spotOrders as $spotOrder)
                            <tr class="table-striped">
                                <td>{{ $spotOrder->id }}</td>
                                <td class="text-heading fw-medium">
                                    <img src="{{ asset($spotOrder->market->baseCurrency->coinLogo()) }}" class="rounded-circle me-1"
                                        width="32px">
                                    <small>{{ $spotOrder->market->name }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $spotOrder->side->color() }} text-uppercase">
                                        {{ $spotOrder->side }}
                                    </span>
                                </td>
                                <td>{{ $spotOrder->type->label() }}</td>
                                <td>
                                    <span class="ms-1 fw-bold font-number">{{ formatNumberTrimZeros($spotOrder->quantity) }}</span>
                                    <small>{{ $spotOrder->market->base_currency }}</small>
                                </td>
                                <td class="fw-bold font-number">
                                    @if ($spotOrder->price === null)
                                        @php
                                            $averagePrice = $spotOrder->getAveragePrice();
                                        @endphp
                                        @if ($averagePrice)
                                            <span class="" data-bs-toggle="tooltip" data-bs-placement="top"
                                                data-bs-custom-class="tooltip-dark"
                                                title="این قیمت میانگین معاملات match شده با این سفارش است">
                                                {{ formatNumberTrimZeros($averagePrice) }}
                                                <i class="fa-regular fa-info-circle ms-1 "></i>
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    @else
                                        {{ formatNumberTrimZeros($spotOrder->price) }}
                                    @endif
                                </td>
                                <td>
                                    <a class="text-heading text-truncate" target="_blank"
                                        href="{{ route('admin.inquiry.user-details', [$spotOrder->user]) }}">
                                        <span class="fw-medium">{{ $spotOrder->user->email }}</span>
                                    </a>
                                </td>
                                <td class="font-number">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="position-relative">
                                            <svg class="" width="40" height="40" viewBox="0 0 36 36">
                                                <circle cx="18" cy="18" r="16" fill="none" stroke="#e9ecef" stroke-width="4">
                                                </circle>
                                                <circle cx="18" cy="18" r="16" fill="none" stroke="#6f38d4" stroke-width="4"
                                                    stroke-dasharray="100"
                                                    stroke-dashoffset="{{ 100 - ($spotOrder->filled_quantity / $spotOrder->quantity) * 100 }}"
                                                    transform="rotate(-90 18 18)"></circle>
                                            </svg>

                                            <span class="font-number position-absolute text-black fw-bold"
                                                style="top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 8px;">{{ formatNumberTrimZeros($spotOrder->filled_quantity / $spotOrder->quantity, 4) * 100 }}%</span>
                                        </div>

                                        {{ formatNumberTrimZeros($spotOrder->filled_quantity) }}
                                        <small>{{ $spotOrder->market->base_currency }}</small>
                                    </div>
                                </td>
                                <td dir="ltr">
                                    {{ \App\Helpers\DateFormatter::convertToPersianDate($spotOrder->created_at, '%Y/%m/%d H:i:s') }}<br>
                                    <small>{{ $spotOrder->created_at->format('Y/m/d') }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-label-{{ $spotOrder->status->color() }}">
                                        {{ $spotOrder->status->label() }}
                                    </span>
                                    @if ($spotOrder->status === \App\Enums\SpotOrderStatusEnum::PARTIALLY_FILLED_CANCELED)
                                        <i class="fa-regular fa-info-circle fa-lg ms-2" data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-custom-class="tooltip-dark"
                                            title="با توجه به اینکه دیگر سفارش از نوع {{ $spotOrder->side === \App\Enums\SpotOrderSideEnum::BUY ? \App\Enums\SpotOrderSideEnum::SELL->label() : \App\Enums\SpotOrderSideEnum::BUY->label() }} برای پر کردن این سفارش موجود نبود یا سفارش به صورت ناقص پر شده مابقی مبلغ به حساب کاربر بازگردانده شد."></i>
                                    @endif
                                </td>
                                <td>
                                    <a href="" class="btn btn-sm btn-icon" data-bs-toggle="modal"
                                        data-bs-target="#order-{{ $spotOrder->id }}">
                                        <i class="fa-light fa-eye fa-lg"></i>
                                    </a>
                                    @if (
                                            request()->input('source', 'user') === 'user' &&
                                            $spotOrder->status === \App\Enums\SpotOrderStatusEnum::OPEN
                                        )
                                        <button type="button" class="btn btn-sm btn-icon btn-danger cancel-single-order"
                                            data-order-id="{{ $spotOrder->id }}" data-bs-toggle="tooltip" title="لغو سفارش">
                                            <i class="fa-light fa-times fa-lg"></i>
                                        </button>
                                    @endif
                                    <div class="modal fade " id="order-{{ $spotOrder->id }}" tabindex="-1" aria-modal="true"
                                        role="dialog">
                                        <div class="modal-dialog modal-xl" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header justify-content-between">
                                                    <div>
                                                        <h5>جزيیات سفارش</h5>
                                                        <h6 class="modal-title font-number mb-2">شماره سفارش
                                                            #{{ $spotOrder->id }}</h6>
                                                    </div>
                                                    <div class="d-flex flex-column ">
                                                        <a href="" class="text-heading text-truncate text-end mb-2">
                                                            <span class="me-1">{{ $spotOrder->user->email }}</span>
                                                            <span class="me-2">({{ $spotOrder->user->username }})</span>
                                                        </a>
                                                    </div>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div
                                                        class="d-flex align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                                        <div class="d-flex flex-wrap gap-2 font-number">
                                                            <span
                                                                class="text-success">{{ formatNumberTrimZeros($spotOrder->filled_quantity / $spotOrder->quantity) * 100 }}%
                                                                اجرا شده</span>
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            <span
                                                                class="me-2 badge bg-{{ $spotOrder->role->color() }}">{{ $spotOrder->role->label() }}</span>
                                                            <h5 class="me-2 fw-bold text-black m-0">
                                                                {{ $spotOrder->market->name }}
                                                                <span class="text-primary">({{ $spotOrder->type->value }})</span>
                                                            </h5>
                                                            <span
                                                                class="text-uppercase badge bg-{{ $spotOrder->side->color() }}">{{ $spotOrder->side }}</span>

                                                        </div>
                                                    </div>
                                                    <div
                                                        class="d-flex align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                                        <h6 class="m-0 mb-2 mb-md-0 me-12">مقدار سفارش</h6>
                                                        <div class="d-flex flex-wrap gap-1 font-number" dir="ltr">
                                                            <span
                                                                class="text-black">{{ formatNumberTrimZeros($spotOrder->quantity) }}
                                                                {{ $spotOrder->market->base_currency }}</span>
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="d-flex align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                                        <h6 class="m-0 mb-2 mb-md-0 me-12">مقدار اجرا شده</h6>
                                                        <div class="d-flex flex-wrap gap-1 font-number" dir="ltr">
                                                            <span
                                                                class="text-info">{{ formatNumberTrimZeros($spotOrder->filled_quantity) }}
                                                                {{ $spotOrder->market->base_currency }}</span>
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="d-flex align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                                        <h6 class="m-0 mb-2 mb-md-0 me-12">قیمت</h6>
                                                        <div class="d-flex flex-wrap gap-1 font-number" dir="ltr">
                                                            <span>{{ $spotOrder->price ? formatNumberTrimZeros($spotOrder->price) : 'سفارش بازار' }}</span>
                                                            <span>{{ $spotOrder->price ? 'USDT' : '' }}</span>
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="d-flex align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                                        <h6 class="m-0 mb-2 mb-md-0 me-12">ارزش کل سفارش</h6>
                                                        <div class="d-flex flex-wrap gap-1 font-number" dir="ltr">
                                                            <span>{{ $spotOrder->price ? formatNumberTrimZeros(bcmul($spotOrder->price, $spotOrder->quantity, 8)) : 'سفارش بازار' }}</span>
                                                            <span>{{ $spotOrder->price ? 'USDT' : '' }}</span>
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="d-flex align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                                        <h6 class="m-0 mb-2 mb-md-0 me-12">ارزش اجرا شده</h6>
                                                        <div class="d-flex flex-wrap gap-1 font-number" dir="ltr">
                                                            @php
                                                                $executedValue = $spotOrder->makerTrades->merge($spotOrder->takerTrades)->sum(function ($trade) {
                                                                    return bcmul($trade->price, $trade->quantity, 8);
                                                                });
                                                            @endphp
                                                            <span>{{ formatNumberTrimZeros($executedValue) }}</span>
                                                            <span>USDT</span>
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="d-flex align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                                        <h6 class="m-0 mb-2 mb-md-0 me-12">ارزش کارمزد Maker</h6>
                                                        <div class="d-flex flex-wrap gap-1 font-number" dir="ltr">
                                                            <span
                                                                class="text-info">{{ formatNumberTrimZeros($spotOrder->total_maker_commission_value) }}
                                                                USDT</span>
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="d-flex align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                                        <h6 class="m-0 mb-2 mb-md-0 me-12">ارزش کارمزد Taker</h6>
                                                        <div class="d-flex flex-wrap gap-1 font-number" dir="ltr">
                                                            <span
                                                                class="text-info">{{ formatNumberTrimZeros($spotOrder->total_taker_commission_value) }}
                                                                USDT</span>
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="d-flex align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                                        <h6 class="m-0 mb-2 mb-md-0 me-12">ارزش کارمزد کل Taker و Maker
                                                        </h6>
                                                        <div class="d-flex flex-wrap gap-1 font-number" dir="ltr">
                                                            <span
                                                                class="text-info">{{ formatNumberTrimZeros($spotOrder->total_commission_value) }}
                                                                USDT</span>
                                                        </div>
                                                    </div>
                                                    <!-- جزییات معاملات اجرا شده -->
                                                    <div class="py-4 mb-4">
                                                        <h5 class="mb-4">جزییات اجرا (Exec. Details)</h5>
                                                        <div class="table-responsive">
                                                            <table class="table table-bordered">
                                                                <thead>
                                                                    <tr>
                                                                        <th>شناسه معامله</th>
                                                                        <th>مقدار</th>
                                                                        <th>قیمت USDT</th>
                                                                        <th>ارزش معامله USDT</th>
                                                                        <th>کارمزد Maker / Taker</th>
                                                                        <th>طرف مقابل</th>
                                                                        <th>تاریخ</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @php
                                                                        $relatedTrades = $spotOrder->makerTrades->merge(
                                                                            $spotOrder->takerTrades,
                                                                        );
                                                                    @endphp

                                                                    @if ($relatedTrades->isEmpty())
                                                                        <tr>
                                                                            <td colspan="8" class="text-center">هیچ
                                                                                معامله‌ای
                                                                                برای این سفارش ثبت نشده است
                                                                            </td>
                                                                        </tr>
                                                                    @else
                                                                        @foreach ($relatedTrades as $trade)
                                                                            <tr>
                                                                                <td>{{ $trade->id }}</td>
                                                                                <td>{{ formatNumberTrimZeros($trade->quantity) }}
                                                                                    <small>{{ $spotOrder->market->base_currency }}</small>
                                                                                </td>
                                                                                <td>{{ formatNumberTrimZeros($trade->price) }}
                                                                                </td>
                                                                                <td class="text-success">
                                                                                    {{ formatNumberTrimZeros($trade->price * $trade->quantity) }}
                                                                                </td>
                                                                                <td class="text-danger" dir="ltr">
                                                                                    <small>{{ formatNumberTrimZeros($trade->commission->maker_commission_amount) }}
                                                                                        {{ $trade->commission->maker_commission_currency }}</small>
                                                                                    <strong> / </strong>
                                                                                    <small>{{ formatNumberTrimZeros($trade->commission->taker_commission_amount) }}
                                                                                        {{ $trade->commission->taker_commission_currency }}</small>
                                                                                </td>
                                                                                <td>
                                                                                    @if ($trade->maker_order_id == $spotOrder->id)
                                                                                        <small>{{ $trade->takerOrder->user->email ?? 'نامشخص' }}</small>
                                                                                    @else
                                                                                        <small>{{ $trade->makerOrder->user->email ?? 'نامشخص' }}</small>
                                                                                    @endif
                                                                                </td>
                                                                                <td dir="ltr">
                                                                                    <small>{{ \App\Helpers\DateFormatter::convertToPersianDate($trade->created_at, '%Y/%m/%d H:i:s') }}</small>
                                                                                </td>
                                                                            </tr>
                                                                        @endforeach
                                                                    @endif
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-label-secondary"
                                                        data-bs-dismiss="modal">بستن
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Pagination Section -->
        @if ($spotOrders->hasPages())
            <div class="row mt-4">

                <div class="col-md-6">
                    <div class="d-flex justify-content-end">
                        {{ $spotOrders->appends(request()->all())->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>

@endsection
@section('vendor-script')
    <script>
        $(document).ready(function () {
            $('[data-bs-toggle="tooltip"]').tooltip();

            // Toggle Advanced Filters
            $('#toggleAdvancedFilter').click(function () {
                const advancedFilters = $('#advancedFilters');
                const button = $(this);
                const icon = button.find('i');

                if (advancedFilters.is(':visible')) {
                    advancedFilters.slideUp();
                    icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                    button.html('<i class="fas fa-chevron-down me-1"></i>نمایش فیلترهای پیشرفته');
                } else {
                    advancedFilters.slideDown();
                    icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                    button.html('<i class="fas fa-chevron-up me-1"></i>مخفی کردن فیلترهای پیشرفته');
                }
            });

            // Clear Filters Button
            $('#clearFilters').click(function () {
                // Get current source parameter
                const currentSource = $('input[name="source"]').val();

                // Redirect to clean URL with only source parameter
                const baseUrl = '{{ route('admin.spot_orders.index') }}';
                const newUrl = baseUrl + '?source=' + currentSource;
                window.location.href = newUrl;
            });

            // Export to Excel Button
            $('#exportFiltered').click(function () {
                const button = $(this);
                const originalText = button.html();

                // Show loading state
                button.prop('disabled', true);
                button.html('<i class="fas fa-spinner fa-spin me-1"></i>در حال تولید...');

                // Get current form data
                const formData = $('#filterForm').serialize();

                // Create export URL
                const exportUrl = '{{ route('admin.spot_orders.excel-export') }}?' + formData;

                // Create temporary link and trigger download
                const link = document.createElement('a');
                link.href = exportUrl;
                link.download = 'spot_orders_' + new Date().toISOString().slice(0, 10) + '.xlsx';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                // Reset button state after delay
                setTimeout(function () {
                    button.prop('disabled', false);
                    button.html(originalText);
                }, 2000);
            });

            // Auto-show advanced filters if any advanced filter has value
            const advancedInputs = ['quantity_min', 'quantity_max', 'price_min', 'price_max'];
            let hasAdvancedValue = false;

            advancedInputs.forEach(function (inputName) {
                if ($('input[name="' + inputName + '"]').val()) {
                    hasAdvancedValue = true;
                }
            });

            if (hasAdvancedValue) {
                $('#advancedFilters').show();
                $('#toggleAdvancedFilter').html('<i class="fas fa-chevron-up me-1"></i>مخفی کردن فیلترهای پیشرفته');
            }

            // Cancel All Open Orders Button
            $('#cancelAllOpenOrders').click(function () {
                if (!confirm('آیا مطمئن هستید که می‌خواهید تمام سفارشات باز را لغو کنید؟\n\nاین عملیات قابل بازگشت نیست.')) {
                    return;
                }

                const button = $(this);
                const originalText = button.html();

                // Show loading state
                button.prop('disabled', true);
                button.html('<i class="fas fa-spinner fa-spin me-1"></i>در حال لغو...');

                $.ajax({
                    url: '{{ route('admin.spot_orders.cancel-all-open') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        if (response.success) {
                            alert(response.message);
                            location.reload();
                        } else {
                            alert('خطا: ' + response.message);
                            button.prop('disabled', false);
                            button.html(originalText);
                        }
                    },
                    error: function (xhr) {
                        let errorMsg = 'خطا در لغو سفارشات';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        alert(errorMsg);
                        button.prop('disabled', false);
                        button.html(originalText);
                    }
                });
            });

            // Cancel Single Order Button
            $(document).on('click', '.cancel-single-order', function () {
                const orderId = $(this).data('order-id');

                if (!confirm('آیا مطمئن هستید که می‌خواهید این سفارش را لغو کنید؟\n\nسفارش #' + orderId + '\n\nاین عملیات قابل بازگشت نیست.')) {
                    return;
                }

                const button = $(this);
                const originalHtml = button.html();

                // Show loading state
                button.prop('disabled', true);
                button.html('<i class="fas fa-spinner fa-spin"></i>');

                $.ajax({
                    url: '{{ route('admin.spot_orders.cancel', ':id') }}'.replace(':id', orderId),
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        if (response.success) {
                            alert(response.message);
                            location.reload();
                        } else {
                            alert('خطا: ' + response.message);
                            button.prop('disabled', false);
                            button.html(originalHtml);
                        }
                    },
                    error: function (xhr) {
                        let errorMsg = 'خطا در لغو سفارش';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        alert(errorMsg);
                        button.prop('disabled', false);
                        button.html(originalHtml);
                    }
                });
            });
        });
    </script>
@endsection

@section('page-style')
    <style>
        .transition-all-3 {
            transition: all 0.3s ease;
        }

        #orderTabs .nav-link:not(.active):hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        #orderTabs .nav-link.active {
            transform: translateY(-1px);
        }

        .nav-pills .nav-link {
            position: relative;
            overflow: hidden;
        }

        .nav-pills .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .nav-pills .nav-link:hover::before {
            left: 100%;
        }
    </style>
@endsection