@extends('dashboard.layout.master')
@section('title', 'مدیریت معاملات Spot')
@section('content')
    {{--    TODO: Complete OTC ORder Card --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left"><span>تعداد معاملات اسپات</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $spotTrades->total() }}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
                            <i class="fa-light fa-swap fa-lg"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        {{--        <div class="col-sm-12 col-xl-3"> --}}
        {{--            <div class="card"> --}}
        {{--                <div class="card-body"> --}}
        {{--                    <div class="d-flex align-items-start justify-content-between"> --}}
        {{--                        <div class="content-left"> --}}
        {{--                            <span>تعداد معاملات امروز</span> --}}
        {{--                            <div class="d-flex align-items-center my-1"> --}}
        {{--                                <h4 class="mb-0 me-2">{{$todayOrderCount}}</h4> --}}
        {{--                            </div> --}}
        {{--                        </div> --}}
        {{--                        <span class="badge bg-label-warning rounded"> --}}
        {{--                            <i class="fa-light fa-swap"></i> --}}
        {{--                        </span> --}}
        {{--                    </div> --}}
        {{--                </div> --}}
        {{--            </div> --}}
        {{--        </div> --}}
        {{--        <div class="col-sm-12 col-xl-3"> --}}
        {{--            <div class="card"> --}}
        {{--                <div class="card-body"> --}}
        {{--                    <div class="d-flex align-items-start justify-content-between"> --}}
        {{--                        <div class="content-left"> --}}
        {{--                            <span>تعداد معاملات خرید</span> --}}
        {{--                            <div class="d-flex align-items-center my-1"> --}}
        {{--                                <h4 class="mb-0 me-2">{{$totalBuyOrderCount}}</h4> --}}
        {{--                            </div> --}}
        {{--                        </div> --}}
        {{--                        <span class="badge bg-label-primary rounded p-2"> --}}
        {{--                            <i class="fa-regular fa-user-tag"></i> --}}
        {{--                        </span> --}}
        {{--                    </div> --}}
        {{--                </div> --}}
        {{--            </div> --}}
        {{--        </div> --}}
        {{--        <div class="col-sm-12 col-xl-3"> --}}
        {{--            <div class="card"> --}}
        {{--                <div class="card-body "> --}}
        {{--                    <div class="d-flex align-items-start justify-content-between"> --}}
        {{--                        <div class="content-left"> --}}
        {{--                            <span>تعداد معاملات فروش</span> --}}
        {{--                            <div class="d-flex align-items-center my-1"> --}}
        {{--                                <h4 class="mb-0 me-2">{{$totalSellOrderCount}}</h4> --}}
        {{--                            </div> --}}
        {{--                        </div> --}}
        {{--                        <span class="badge bg-label-primary rounded p-2"> --}}
        {{--                            <i class="fa-regular fa-user-tag"></i> --}}
        {{--                        </span> --}}
        {{--                    </div> --}}
        {{--                </div> --}}
        {{--            </div> --}}
        {{--        </div> --}}
        {{--        <div class="col-sm-12 col-xl-3"> --}}
        {{--            <div class="card"> --}}
        {{--                <div class="card-body"> --}}
        {{--                    <div class="d-flex align-items-start justify-content-between"> --}}
        {{--                        <div class="content-left"> --}}
        {{--                            <span>حجم معاملات</span> --}}
        {{--                            <div class="d-flex align-items-center my-1"> --}}
        {{--                                <h4 class="mb-0 me-2">{{formatNumber($totalOrdersValue)}} --}}
        {{--                                <small>USDT</small> --}}
        {{--                                </h4> --}}
        {{--                            </div> --}}
        {{--                        </div> --}}
        {{--                        <span class="badge bg-label-primary rounded p-2"> --}}
        {{--                            <i class="fa-regular fa-user-tag"></i> --}}
        {{--                        </span> --}}
        {{--                    </div> --}}
        {{--                </div> --}}
        {{--            </div> --}}
        {{--        </div> --}}
        {{--        <div class="col-sm-12 col-xl-3"> --}}
        {{--            <div class="card"> --}}
        {{--                <div class="card-body bg-success"> --}}
        {{--                    <div class="d-flex align-items-start justify-content-between"> --}}
        {{--                        <div class="content-left"> --}}
        {{--                            <span class="text-white">کاربران با بیشترین معامله امروز</span> --}}
        {{--                            <div class="d-flex align-items-baseline my-1"> --}}
        {{--                                <small class="text-white mx-2"> حجم معاملات امروز: </small> --}}
        {{--                                <h4 class="mb-0 me-2 text-primary">{{formatNumber($totalTodayOrdersValue,2)}}</h4> --}}
        {{--                                <small class="text-primary">USDT</small> --}}
        {{--                            </div> --}}
        {{--                        </div> --}}

        {{--                        <ul class="list-unstyled avatar-group d-flex my-0"> --}}
        {{--                            @if (count($topUsers)) --}}
        {{--                                @foreach ($topUsers as $topUser) --}}
        {{--                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-html='true' --}}
        {{--                                        data-bs-placement="top" class="avatar pull-up" --}}
        {{--                                        title="<span class='fw-medium'>نام:</span> --}}
        {{--                                                    {{ $topUser['user']->fullname()}}</span> --}}
        {{--                                                    <br> <span class='fw-medium'>شناسه کاربری:</span> --}}
        {{--                                                    <span class='fw-medium font-monospace'>({{ $topUser['user']->id }}#)</span> --}}
        {{--                                                    <br> <span class='fw-medium'>نام کاربری:</span> --}}
        {{--                                                    <span class='fw-medium font-monospace'>({{ $topUser['user']->username }})</span> --}}
        {{--                                                    <br> <span class='fw-medium'>مجموع واریز:</span> --}}
        {{--                                                    <span class='fw-medium font-monospace'>{{ formatNumberTrimZeros($topUser['totalOrders']) }}$</span> --}}
        {{--                                                    "> --}}
        {{--                                        <div class="avatar me-2"> --}}
        {{--                                            @php --}}
        {{--                                                // Define your color array --}}
        {{--                                                $colors = ['primary', 'info', 'danger', 'warning','success']; --}}

        {{--                                                // Get a random index from the array --}}
        {{--                                                $randomIndex = array_rand($colors); --}}

        {{--                                                // Retrieve the color using the random index --}}
        {{--                                                $randomColor = $colors[$randomIndex]; --}}
        {{--                                            @endphp --}}
        {{--                                            <span --}}
        {{--                                                class="avatar-initial rounded-circle bg-label-{{$randomColor}}">{{$topUser['user']->avatar_user_name}}</span> --}}
        {{--                                        </div> --}}
        {{--                                    </li> --}}
        {{--                                @endforeach --}}
        {{--                            @else --}}
        {{--                                بدون معامله --}}
        {{--                            @endif --}}

        {{--                        </ul> --}}

        {{--                    </div> --}}
        {{--                </div> --}}
        {{--            </div> --}}
        {{--        </div> --}}
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
                <h5 class="m-0 me-2">لیست معاملات اسپات</h5>

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
                        <th>
                            @php
                                $currentParams = request()->except('sortByQuantity');
                                $currentSortDirection = request()->input('sortByQuantity', 'desc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.spot_trades.index', array_merge($currentParams, ['sortByQuantity' => $newSortDirection])) }}"
                                class="text-black">
                                مقدار
                                @if ($currentSortDirection === 'asc')
                                    <span><i class="fa-solid fa-arrow-up"></i></span>
                                @else
                                    <span><i class="fa-solid fa-arrow-down"></i></span>
                                @endif
                            </a>
                        </th>
                        <th>قیمت</th>
                        <th>کاربر Maker</th>
                        <th>کاربر Taker</th>
                        <th>کارمزد کل
                            <i class="fa-regular fa-info-circle" data-bs-toggle="tooltip" data-bs-placement="top"
                                data-bs-custom-class="tooltip-dark" title="مجموع ارزش کارمزد maker و taker"></i>
                        </th>
                        <th>
                            @php
                                $currentParams = request()->except('sortByCreatedAt');
                                $currentSortDirection = request()->input('sortByCreatedAt', 'desc');
                                $newSortDirection = $currentSortDirection === 'asc' ? 'desc' : 'asc';
                            @endphp
                            <a href="{{ route('admin.otc_orders.index', array_merge($currentParams, ['sortByCreatedAt' => $newSortDirection])) }}"
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
                    @if ($spotTrades->isEmpty())
                        <tr>
                            <td colspan="14" class="text-center">تراکنشی یافت نشد.</td>
                        </tr>
                    @else
                        @foreach ($spotTrades as $spotTrade)
                            <tr class="table-striped">
                                <td>{{ $spotTrade->id }}</td>
                                <td class="text-heading fw-medium">
                                    <img src="{{ asset($spotTrade->market->baseCurrency->coinLogo()) }}"
                                        class="rounded-circle" width="32px">
                                    <small>{{ $spotTrade->market->name }}</small>
                                </td>

                                <td>{{ $spotTrade->takerOrder->type->label() }}</td>
                                <td>
                                    <span class="ms-1">{{ formatNumberTrimZeros($spotTrade->quantity) }}</span>
                                    <small>{{ $spotTrade->market->base_currency }}</small>
                                </td>
                                <td dir="ltr">{{ formatNumberTrimZeros($spotTrade->price) }}
                                    <small>USDT</small>
                                </td>
                                <td>{{ $spotTrade->makerOrder->user->email }}
                                    <div class="mt-1">
                                        سمت: <span
                                            class="badge bg-{{ $spotTrade->makerSide === 'BUY' ? 'success' : 'danger' }}">
                                            {{ $spotTrade->makerSide }} </span>
                                    </div>

                                </td>
                                <td>{{ $spotTrade->takerOrder->user->email }}
                                    <div class="mt-1">
                                        سمت: <span
                                            class="badge bg-{{ $spotTrade->takerSide === 'BUY' ? 'success' : 'danger' }}">
                                            {{ $spotTrade->takerSide }} </span>
                                    </div>
                                </td>
                                <td class="text-info" dir="ltr">
                                    {{ formatNumberTrimZeros($spotTrade->total_commission_value) }}
                                    <small>USDT</small>
                                </td>
                                <td dir="ltr">{{ jdate($spotTrade->created_at)->format('Y-m-d H:i') }}</td>
                                <td>
                                    <span class="badge bg-label-success">
                                        {{ $spotTrade->makerOrder->status->label() }}
                                    </span>
                                </td>
                                <td>
                                    <a href="" class="btn btn-sm btn-icon" data-bs-toggle="modal"
                                        data-bs-target="#trade-{{ $spotTrade->id }}">
                                        <i class="fa-light fa-eye"></i>
                                    </a>
                                    <div class="modal fade " id="trade-{{ $spotTrade->id }}" tabindex="-1"
                                        aria-modal="true" role="dialog">
                                        <div class="modal-dialog modal-xl" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header justify-content-between">

                                                    <div>
                                                        <h5>جزيیات معامله</h5>
                                                        <h6 class="modal-title font-number mb-2">شماره معامله
                                                            #{{ $spotTrade->id }}</h6>
                                                        <div class="d-flex gap-2 mb-2 align-items-baseline">
                                                            <span>شماره سفارش Maker --></span>
                                                            <h6 class="modal-title font-number">
                                                                #{{ $spotTrade->maker_order_id }}</h6>
                                                        </div>
                                                        <div class="d-flex gap-2 mb-2 align-items-baseline">
                                                            <span>شماره سفارش Taker --></span>
                                                            <h6 class="modal-title font-number">
                                                                #{{ $spotTrade->taker_order_id }}</h6>
                                                        </div>

                                                    </div>

                                                    <div class="d-flex flex-column ">


                                                        <div dir="ltr" class="text-end">
                                                            <span class="fw-bold">Maker:</span>
                                                            <span>{{ $spotTrade->makerOrder->user->email }}
                                                                ({{ $spotTrade->makerOrder->user->username }})
                                                            </span>

                                                            <span
                                                                class="badge bg-{{ $spotTrade->makerSide === 'BUY' ? 'success' : 'danger' }}">
                                                                {{ $spotTrade->makerSide }} </span>
                                                        </div>


                                                        <div dir="ltr" class="mt-2 text-end">
                                                            <span class="fw-bold">Taker:</span>
                                                            <span>{{ $spotTrade->takerOrder->user->email }}
                                                                ({{ $spotTrade->takerOrder->user->username }})
                                                            </span>

                                                            <span
                                                                class="badge bg-{{ $spotTrade->takerSide === 'BUY' ? 'success' : 'danger' }}">
                                                                {{ $spotTrade->takerSide }} </span>
                                                        </div>


                                                    </div>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div
                                                        class="d-flex align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                                        <div class="d-flex flex-wrap gap-2 font-number">

                                                            <span
                                                                class="text-success">{{ formatNumber(($spotTrade->quantity / $spotTrade->makerOrder->quantity) * 100) }}
                                                                درصد از کل سفارش</span>
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            <h5 class="fw-bold text-black m-0">
                                                                {{ $spotTrade->market->name }}
                                                                <span
                                                                    class="text-primary">({{ $spotTrade->takerOrder->type }})</span>
                                                            </h5>

                                                        </div>
                                                    </div>
                                                    <div
                                                        class="d-flex align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                                        <h6 class="m-0 mb-2 mb-md-0 me-12">کارمزد Maker</h6>
                                                        <div class="d-flex flex-wrap gap-1 font-number" dir="ltr">

                                                            <span
                                                                class="text-black">{{ formatNumberTrimZeros($spotTrade->commission->maker_commission_amount) }}
                                                                {{ $spotTrade->commission->maker_commission_currency }}</span>

                                                            <small
                                                                class="me-2">({{ formatNumberTrimZeros($spotTrade->maker_commission_value) }}
                                                                USDT)</small>
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="d-flex align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                                        <h6 class="m-0 mb-2 mb-md-0 me-12">کارمزد Taker</h6>
                                                        <div class="d-flex flex-wrap gap-1 font-number" dir="ltr">
                                                            @if ($spotTrade->takerSide === \App\Enums\SpotOrderSideEnum::BUY->value)
                                                                <span>{{ formatNumberTrimZeros($spotTrade->commission->taker_commission_amount) }}
                                                                    {{ $spotTrade->market->base_currency }}</span>
                                                            @else
                                                                <span>{{ formatNumberTrimZeros($spotTrade->commission->taker_commission_amount) }}
                                                                    {{ $spotTrade->market->quote_currency }}</span>
                                                            @endif
                                                            <br>
                                                            <small>({{ formatNumberTrimZeros($spotTrade->taker_commission_value) }}
                                                                USDT)</small>
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="d-flex align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                                        <h6 class="m-0 mb-2 mb-md-0 me-12">مجموع کارمزد (USDT)</h6>
                                                        <div class="d-flex flex-wrap gap-1 font-number" dir="ltr">
                                                            <span
                                                                class="text-info">{{ formatNumberTrimZeros($spotTrade->total_commission_value) }}
                                                                USDT</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-label-secondary"
                                                        data-bs-dismiss="modal">بستن</button>
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
        <div class="row mt-4">
            <div class="col-md-12">
                {{ $spotTrades->appends(request()->all())->links() }}
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
