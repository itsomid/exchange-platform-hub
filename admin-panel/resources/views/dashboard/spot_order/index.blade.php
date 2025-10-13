@extends('dashboard.layout.master')
@section('title', 'مدیریت سفارشات Spot')
@section('content')
    {{--    TODO: Complete OTC ORder Card --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left"><span>تعداد سفارشات اسپات</span>
                            <div class="d-flex align-items-center my-1">
                                {{--                                <h4 class="mb-0 me-2">{{$spotOrders->total()}}</h4> --}}
                            </div>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
                            <i class="fa-light fa-swap fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                            {{ number_format($userOrdersCount ?? 0) }}
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
                            {{ number_format($botOrdersCount ?? 0) }}
                        </span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">فیلتر سفارشات اسپات</h5>
            </div>
            <form action="{{ route('admin.spot_orders.index') }}" method="get">
                <!-- Hidden input to maintain current source -->
                <input type="hidden" name="source" value="{{ request()->input('source', 'user') }}">

                <div class="row">
                    <div class="col-md-3 mt-3">
                        <div class="form-group">
                            <label class="form-label" for="type">نوع سفارش:</label>
                            <select name="type" class="form-control" id="type">
                                <option value=" ">همه</option>
                                @foreach (\App\Enums\SpotOrderTypeEnum::cases() as $case)
                                    <option value="{{ $case->name }}"
                                        {{ request()->has('type') && request()->input('type') == $case->name ? 'selected' : '' }}>
                                        {{ $case->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if (request()->input('source', 'user') === 'user')
                        <div class="col-md-6 mt-3">
                            <label class="form-label" for="user">کاربر :</label>
                            <x-user-selection-component input-name="user" multiple="0"
                                selected="{{ request()->filled('user') && $spotOrders->isNotEmpty() && $spotOrders[0]->user ? $spotOrders[0]->user->id : '' }}"
                                selected-label="{{ request()->filled('user') && $spotOrders->isNotEmpty() && $spotOrders[0]->user
                                    ? '(' . $spotOrders[0]->user->id . '#) ' . $spotOrders[0]->user->fullname() . ' | ' . $spotOrders[0]->user->email
                                    : '' }}"></x-user-selection-component>
                        </div>
                    @endif

                    <div class="col-md-2 mt-3">
                        <div class="form-group"><br>
                            <button class="btn btn-success text-white" type="submit">
                                <span>فیلتر</span><i class="fas fa-filter mx-3"></i>
                            </button>
                        </div>
                    </div>
                </div>
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
                        <th>بازار</th>
                        <th>سمت</th>
                        <th>نوع</th>
                        <th>
                            @php
                                $currentParams = request()->except('sortByQuantity');
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
                        <th>قیمت سفارش (USDT)</th>
                        @if (request()->input('source', 'user') === 'user')
                            <th>کاربر</th>
                        @else
                            <th>منبع</th>
                        @endif
                        <th>مقدار اجرا شده</th>
                        <th>
                            @php
                                $currentParams = request()->except('sortByCreatedAt');
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
                        <th>وضعیت سفارش</th>
                        <th>جزییات</th>
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
                                    <img src="{{ asset($spotOrder->market->baseCurrency->coinLogo()) }}"
                                        class="rounded-circle" width="32px">
                                    <small>{{ $spotOrder->market->name }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $spotOrder->side->color() }} text-uppercase">
                                        {{ $spotOrder->side }}
                                    </span>
                                </td>
                                <td>{{ $spotOrder->type->label() }}</td>
                                <td>
                                    <span class="ms-1 fw-bold">{{ formatNumberTrimZeros($spotOrder->quantity) }}</span>
                                    <small>{{ $spotOrder->market->base_currency }}</small>
                                </td>
                                <td class="fw-bold">{{ formatNumberTrimZeros($spotOrder->price) }}
                                </td>
                                <td>{{ $spotOrder->user->email }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="position-relative">
                                            <svg class="" width="40" height="40" viewBox="0 0 36 36">
                                                <circle cx="18" cy="18" r="16" fill="none"
                                                    stroke="#e9ecef" stroke-width="4"></circle>
                                                <circle cx="18" cy="18" r="16" fill="none"
                                                    stroke="#6f38d4" stroke-width="4" stroke-dasharray="100"
                                                    stroke-dashoffset="{{ 100 - formatNumberTrimZeros(($spotOrder->filled_quantity / $spotOrder->quantity) * 100) }}"
                                                    transform="rotate(-90 18 18)"></circle>
                                            </svg>

                                            <span class="font-number position-absolute text-black fw-light"
                                                style="font-size: 13px; top: 11px; right: 6px">{{ round(($spotOrder->filled_quantity / $spotOrder->quantity) * 100) }}%</span>
                                        </div>

                                        {{ formatNumberTrimZeros($spotOrder->filled_quantity) }}
                                        <small>{{ $spotOrder->market->base_currency }}</small>
                                    </div>
                                </td>
                                <td dir="ltr">{{ jdate($spotOrder->created_at)->format('Y-m-d H:i') }}</td>
                                <td>
                                    <span class="badge bg-label-{{ $spotOrder->status->color() }}">
                                        {{ $spotOrder->status->label() }}

                                    </span>
                                    @if ($spotOrder->status === \App\Enums\SpotOrderStatusEnum::PARTIALLY_FILLED_CANCELED)
                                        <i class="fa-regular fa-info-circle fa-lg ms-2" data-bs-toggle="tooltip"
                                            data-bs-placement="top" data-bs-custom-class="tooltip-dark"
                                            title="با توجه به اینکه دیگر سفارش از نوع {{ $spotOrder->side === \App\Enums\SpotOrderSideEnum::BUY ? \App\Enums\SpotOrderSideEnum::SELL->label() : \App\Enums\SpotOrderSideEnum::BUY->label() }} برای پر کردن این سفارش موجود نبود. سفارش به صورت ناقص پر شده و مابقی مبلغ به حساب کاربر بازگردانده شد."></i>
                                    @endif
                                </td>
                                <td>
                                    <a href="" class="btn btn-sm btn-icon" data-bs-toggle="modal"
                                        data-bs-target="#order-{{ $spotOrder->id }}">
                                        <i class="fa-light fa-eye fa-lg"></i>
                                    </a>
                                    <div class="modal fade " id="order-{{ $spotOrder->id }}" tabindex="-1"
                                        aria-modal="true" role="dialog" {{--                                     style="display: block" --}}>
                                        <div class="modal-dialog modal-xl" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header justify-content-between">
                                                    <div>
                                                        <h5>جزيیات سفارش</h5>
                                                        <h6 class="modal-title font-number mb-2">شماره سفارش
                                                            #{{ $spotOrder->id }}</h6>
                                                    </div>
                                                    <div class="d-flex flex-column ">
                                                        <a href=""
                                                            class="text-heading text-truncate text-end mb-2">
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
                                                                class="text-success">{{ formatNumberTrimZeros($spotOrder->filled_quantity / $spotOrder->quantity ) * 100 }}%
                                                                اجرا شده</span>
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            <span
                                                                class="me-2 badge bg-{{ $spotOrder->role->color() }}">{{ $spotOrder->role->label() }}</span>
                                                            <h5 class="me-2 fw-bold text-black m-0">
                                                                {{ $spotOrder->market->name }}
                                                                <span
                                                                    class="text-primary">({{ $spotOrder->type->value }})</span>
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

                                                            <span>{{ $spotOrder->price ? formatNumberTrimZeros(bcmul($spotOrder->price, $spotOrder->filled_quantity, 8)) : 'سفارش بازار' }}</span>
                                                            <span>{{ $spotOrder->price ? 'USDT' : '' }}</span>
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
                                                                                <td>{{ formatNumberTrimZeros($trade->price) }}</td>
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
        $(document).ready(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
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
