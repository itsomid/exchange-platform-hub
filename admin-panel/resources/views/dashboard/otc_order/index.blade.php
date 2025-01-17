@extends('dashboard.layout.master')
@section('title', 'مدیریت معاملات')
@section('content')
    {{--    TODO: Complete OTC ORder Card--}}
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left"><span>تعداد معاملات</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{count($otcOrders)}}</h4>
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
                            <span>تعداد معاملات امروز</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{count($otcOrders)}}</h4>
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
                            <span>تعداد معاملات خرید</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{count($otcOrders)}}</h4>
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
                                <h4 class="mb-0 me-2 text-primary">{{formatNumber($totalOrdersValue,2)}}</h4>
                                <small class="text-primary">USDT</small>
                            </div>
                        </div>

                        <ul class="list-unstyled avatar-group d-flex my-0">
                            @if(count($topUsers))
                                @foreach($topUsers as $topUser)
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-html='true'
                                        data-bs-placement="top" class="avatar pull-up"
                                        title="<span class='fw-medium'>نام:</span>
                                                    {{ $topUser['user']->fullname()}}</span>
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
                                                $colors = ['primary', 'info', 'danger', 'warning','success'];

                                                // Get a random index from the array
                                                $randomIndex = array_rand($colors);

                                                // Retrieve the color using the random index
                                                $randomColor = $colors[$randomIndex];
                                            @endphp
                                            <span
                                                class="avatar-initial rounded-circle bg-label-{{$randomColor}}">{{$topUser['user']->avatar_user_name}}</span>
                                        </div>
                                        {{--                                        <img class="rounded-circle" src="{{ $topUser['user']->avatar_url ?? 'http://127.0.0.1:8000/images/avatars/male/2.png' }}">--}}
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

    <div class="card">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">فیلتر</h5>
            </div>
            <form action="{{route('admin.otc_orders.index')}}" method="get">
                <div class="row">
                    <div class="col-md-3 mt-3">
                        <div class="form-group">
                            <label class="form-label" for="type">نوع تراکنش:</label>
                            <select name="type" class="form-control" id="type">
                                <option value=" ">همه</option>
                                @foreach(\App\Enums\OTCOrderTypeEnum::cases() as $case)
                                    <option
                                        value="{{$case->name}}" {{request()->has('type') && request()->input('type') == $case->name ? 'selected' : "" }}>
                                        {{$case->label()}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 mt-3">
                        <label class="form-label" for="user">کاربر :</label>
                        <x-user-selection-component
                            input-name="user"
                            multiple="0"
                            selected="{{ request()->filled('user')?$otcOrders[0]->user->id : '' }}"
                            selected-label="{{ request()->filled('user')
                                ? '('.$otcOrders[0]->user->id.'#) '.$otcOrders[0]->user->fullname().' | '.$otcOrders[0]->user->email
                                : '' }}"
                        ></x-user-selection-component>
                    </div>
                    <div class="w-100"></div>
                    <div class="col-md-2">
                        <div class="form-group mt-3"><br>
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
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>شناسه</th>
                    <th>بازار</th>
                    <th>نوع معامله</th>
                    <th>کاربر</th>
                    <th>
                        @php
                            $currentParams = request()->except('sortByAmount');
                            $newSortDirection = request()->input('sortByAmount') == 'asc' ? 'desc' : 'asc';
                        @endphp
                        <a href="{{ route('admin.transaction.index', array_merge($currentParams, ['sortByAmount' => $newSortDirection])) }}"
                           class="text-black">
                            مقدار
                            @if( request()->input('sortByAmount') == 'asc')
                                <span>&uarr;</span>
                            @else
                                <span>&darr;</span>
                            @endif
                        </a>
                    </th>
                    <th>قیمت</th>
                    <th>ارزش</th>
                    <th>کارمزد</th>
                    <th>دریافتی</th>
                    <th>
                        @php
                            $currentParams = request()->except('sortByCreatedAt');
                            $newSortDirection = request()->input('sortByCreatedAt') == 'asc' ? 'desc' : 'asc';
                        @endphp
                        <a href="{{ route('admin.transaction.index', array_merge($currentParams, ['sortByCreatedAt' => $newSortDirection])) }}"
                           class="text-black">
                            تاریخ و زمان
                            @if( request()->input('sortByCreatedAt') == 'asc')
                                <span>&uarr;</span>
                            @else
                                <span>&darr;</span>
                            @endif
                        </a>
                    </th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                @if($otcOrders->isEmpty())
                    <tr>
                        <td colspan="9" class="text-center">تراکنشی یافت نشد.</td>
                    </tr>
                @else

                    @foreach($otcOrders as $order)
                        <tr>
                            <td>{{$order->id}}</td>
                            <td class="text-heading fw-medium">
                                <img src="{{asset($order->market->baseCurrency->coinLogo())}}"
                                     class="rounded-circle" width="32px">
                                {{$order->market->name}}
                            </td>
                            <td>
                                <span class="badge bg-label-{{$order->type->color()}}">{{$order->type->label()}}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <a href="" class="text-heading text-truncate">
                                        <span class="fw-medium">{{$order->user->email}}</span>
                                    </a>
                                    <small>{{$order->user->username}}</small>
                                </div>
                            </td>

                            <td class="font-number" dir="ltr">
                                <span class="ms-2">{{formatNumberTrimZeros($order->quantity)}}</span>
                                <small>{{$order->market->baseCurrency->symbol}}</small>
                            </td>
                            <td class="font-number" dir="ltr">
                                <span class="ms-2">{{formatNumberTrimZeros($order->price)}}</span>
                                <small>{{$order->market->quoteCurrency->symbol}}</small>
                            </td>
                            <td class="font-number" dir="ltr">
                                {{formatNumberTrimZeros($order->price * $order->quantity)}}
                                <small>USDT</small>
                            </td>
                            <td class="font-number" dir="ltr">
                                {{formatNumberTrimZeros($order->fee)}}

                                <small>{{$order->type === \App\Enums\OTCOrderTypeEnum::BUY ? $order->market->baseCurrency->symbol : $order->market->quoteCurrency->symbol}}</small>
                            </td>
                            <td class="font-number" dir="ltr">
                                @if($order->type === \App\Enums\OTCOrderTypeEnum::BUY)
                                    {{formatNumberTrimZeros($order->quantity -  $order->fee)}}
                                    <small>{{$order->market->baseCurrency->symbol}}</small>
                                @else
                                    {{formatNumberTrimZeros(($order->price * $order->quantity) -  $order->fee)}}
                                    <small>{{$order->market->quoteCurrency->symbol}}</small>
                                @endif
                            </td>
                            <td class="font-number">
                                {{\App\Helpers\DateFormatter::convertToPersianDate($order->created_at,'H:i:s %Y/%m/%d')}}
                            </td>

                            <td>
                                <span class="badge bg-label-success">{{$order->status->label()}}</span>
                            </td>
                            <td>
                                <a href="" class="btn btn-icon btn-text-secondary" data-bs-toggle="modal"
                                   data-bs-target="#otc-{{$order->id}}">
                                    <i class="fa-light fa-memo-circle-info fa-xl"></i>
                                </a>
                                <div class="modal fade" id="otc-{{$order->id}}" tabindex="-1" aria-model="true"
                                     role="dialog">
                                    <div class="modal-dialog modal-xl" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title font-number" id="exampleModalLabel4">تراکنش های
                                                    معامله #{{$order->id}}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="table-responsive text-nowrap">
                                                    <table class="table table-striped">
                                                        <thead>
                                                        <tr>
                                                            <th>شناسه</th>
                                                            <th>نوع تراکنش</th>
                                                            <th>رمز ارز</th>
                                                            <th>مقدار</th>
                                                            <th>مقدار موجودی</th>
                                                            <th>توضیحات</th>
                                                            <th>تاریخ و زمان</th>
                                                            <th>وضعیت</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody class="table-border-bottom-0">
                                                        @if($order->transactions->isEmpty())
                                                            <tr>
                                                                <td colspan="9" class="text-center">تراکنشی یافت نشد.
                                                                </td>
                                                            </tr>
                                                        @else

                                                            @foreach($order->transactions as $transaction)

                                                                <tr>
                                                                    <td>{{$transaction->id}}</td>
                                                                    <td class="text-heading fw-medium">
                                                                        <div
                                                                            class="d-flex justify-content-start align-items-center">
                                                                            <div
                                                                                class="trans-avatar-group d-flex align-items-center assigned-avatar">
                                                                                <div class="avatar avatar-md ">
                                                                                    <img
                                                                                        src="{{asset($transaction->wallet->currency->coinLogo())}}"
                                                                                        class="rounded-circle">
                                                                                </div>
                                                                                <div class="avatar avatar-md">
                                                                                <span
                                                                                    class="avatar-initial rounded-circle bg-label-{{$transaction->type->color()}}">
                                                                                    <i class="fa-regular fa-{{$transaction->type->icon()}} mx-3"></i>
                                                                                </span>
                                                                                </div>
                                                                            </div>
                                                                            <div
                                                                                class="d-flex flex-column align-items-start">
                                                                                    <span
                                                                                        class="badge bg-label-{{$transaction->type->color()}} ms-2">
                                                                                        {{$transaction->type->label()}}
                                                                                    </span>
                                                                                @if($transaction->subtype->value != 'user_initiated')
                                                                                    <span
                                                                                        class="badge bg-label-secondary ms-2 mt-2">
                                                                                         {{$transaction->subtype->label()}}
                                                                                    </span>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    </td>


                                                                    <td>{{$transaction->wallet->currency_symbol}}</td>
                                                                    <td class="font-number" dir="ltr">
                                                                        <h6 class="mb-0 {{$transaction->amount > 0 ?'text-success': 'text-danger'}}">{{formatNumberTrimZeros($transaction->amount)}}</h6>
                                                                    </td>
                                                                    <td class="font-number">
                                                                        <h6 class="mb-0">{{formatNumberTrimZeros($transaction->balance)}}</h6>
                                                                    </td>

                                                                    <td class="font-number text-wrap">
                                                                        @if($transaction->admin_id)
                                                                            {{$transaction->admin->last_name}}
                                                                        @endif
                                                                        <span>{{$transaction->description}}</span>

                                                                    </td>
                                                                    <td class="font-number">
                                                                        {{\App\Helpers\DateFormatter::convertToPersianDate($transaction->created_at,'H:i:s %Y/%m/%d')}}
                                                                    </td>

                                                                    <td>
                                                                        <span
                                                                            class="badge bg-label-{{$transaction->status->color()}}">
                                                                            {{$transaction->status->label()}}
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        @endif
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-label-secondary waves-effect"
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
        {{--            {{$transactions->appends()->links()}}--}}
    </div>

@endsection
@section('vendor-script')
    @vite([
          ])
@endsection
@section('vendor-style')
    @vite([])
@endsection
