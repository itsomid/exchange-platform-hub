@extends('dashboard.layout.master')
@section('title', 'مدیریت معاملات')
@section('content')
    {{--    TODO: Complete OTC ORder Card --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-3">
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
        <div class="col-sm-12 col-xl-3">
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
        <div class="col-sm-12 col-xl-3">
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
        <div class="col-sm-12 col-xl-3">
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
        <div class="col-sm-12 col-xl-3">
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
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body bg-success">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-white">کاربران با بیشترین معامله امروز</span>
                            <div class="d-flex align-items-baseline my-1">
                                <small class="text-white mx-2"> حجم معاملات امروز: </small>
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
            <h5 class="card-title">خروجی اکسل</h5>
            <form class="row mt-3 d-flex align-items-end"
                action="{{ route('admin.otc_orders.excel-export', request()->query()) }}" method="POST">
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

    <div class="card">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">فیلتر</h5>
            </div>
            <form action="{{ route('admin.otc_orders.index') }}" method="get">
                <div class="row">
                    <div class="col-md-3 mt-3">
                        <div class="form-group">
                            <label class="form-label" for="type">نوع تراکنش:</label>
                            <select name="type" class="form-control" id="type">
                                <option value=" ">همه</option>
                                @foreach (\App\Enums\OTCOrderTypeEnum::cases() as $case)
                                    <option value="{{ $case->name }}"
                                        {{ request()->has('type') && request()->input('type') == $case->name ? 'selected' : '' }}>
                                        {{ $case->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 mt-3">
                        <label class="form-label" for="user">کاربر :</label>
                        <x-user-selection-component input-name="user" multiple="0"
                            selected="{{ request()->filled('user') ? $otcOrders[0]->user->id : '' }}"
                            selected-label="{{ request()->filled('user')
                                ? '(' . $otcOrders[0]->user->id . '#) ' . $otcOrders[0]->user->fullname() . ' | ' . $otcOrders[0]->user->email
                                : '' }}"></x-user-selection-component>
                    </div>
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
                        <th>عملیات</th>
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
                                </td>
                                <td>
                                    <a href="" class="btn btn-icon btn-text-secondary" data-bs-toggle="modal"
                                        data-bs-target="#otc-{{ $order->id }}">
                                        <i class="fa-light fa-memo-circle-info fa-xl"></i>
                                    </a>
                                    @if ($order->status === \App\Enums\OTCOrderStatusEnum::CANCELED)
                                        <a href="#" class="btn btn-icon btn-text-secondary" data-bs-toggle="modal"
                                            data-bs-target="#otc-description-{{ $order->id }}">
                                            <i class="fa-regular fa-eye fa-xl"></i>
                                        </a>
                                    @endif
                                    @include('dashboard.otc_order.otc-description-modal', [
                                        'order' => $order,
                                    ])
                                    @include('dashboard.otc_order.otc-detail-modal', [
                                        'order' => $order,
                                    ])
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        <div class="row mt-4">
            <div class="col-md-12">
                {{ $otcOrders->appends(request()->all())->links() }}
            </div>
        </div>
    </div>

@endsection
@section('vendor-script')
    <script>
        $(document).ready(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
@endsection
