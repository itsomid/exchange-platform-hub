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
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">?</h4>
                            </div>
                        </div>
                        <ul class="list-unstyled avatar-group d-flex my-0">
                            @foreach($otcOrders as $order)
                                {{--                                <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"--}}
                                {{--                                    title="{{$transaction->user->email}}" class="avatar pull-up">--}}
                                {{--                                    <img class="rounded-circle" src="http://127.0.0.1:8000/images/avatars/male/2.png" alt="Avatar">--}}
                                {{--                                </li>--}}
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="card">
            <div class="card-body">
                <div class="card-title header-elements">
                    <h5 class="m-0 me-2">فیلتر</h5>
                </div>
                <form action="{{route('admin.transaction.index')}}" method="get">
                    <div class="row">
                        <div class="col-md-3 mt-3">
                            <div class="form-group">
                                <label class="form-label" for="type">نوع تراکنش:</label>
                                <select name="type" class="form-control" id="type">
                                    <option value=" ">همه</option>
                                    @foreach(\App\Enums\TransactionTypeEnum::cases() as $case)
                                        <option
                                            value="{{$case->name}}" {{request()->has('type') && request()->input('type') == $case->name ? 'selected' : "" }}>
                                            {{\App\Enums\TransactionTypeEnum::TYPE_LABEL[$case->value]}}
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
                                selected=""
                                selected-label=""
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
    </div>

    <div class="row mt-5">
        <div class="card">
            <div class="card-body">
                <div class="card-title header-elements">
                    <h5 class="m-0 me-2">لیست معاملات OTC</h5>
                    <div class="card-title-elements ms-auto">
                        <a href="{{route('admin.wallet.increase-credit')}}" class="btn btn-primary">
                            <i class="fa fa-plus mx-2"></i> افزایش اعتبار
                        </a>
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
                            <th>قیمت</th>
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
                            <th>ارزش</th>
                            <th>کارمزد</th>
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
                                             class="rounded-circle">
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
                                        <h6 class="mb-0">{{formatNumberTrimZeros($order->price)}}</h6>
                                    </td>
                                    <td class="font-number">
                                        <h6 class="mb-0">{{formatNumberTrimZeros($order->quantity)}}</h6>
                                    </td>
                                    <td class="font-number">
                                        {{formatNumberTrimZeros($order->price * $order->quantity)}}
                                        <small>USDT</small>
                                    </td>
                                    <td class="font-number">
                                        {{formatNumberTrimZeros($order->fee)}}
                                        <small>{{$order->market->baseCurrency->symbol}}</small>
                                    </td>
                                    <td class="font-number">
                                        {{\App\Helpers\DateFormatter::convertToPersianDate($order->created_at,'H:i:s %Y/%m/%d')}}
                                    </td>

                                    <td>
                                        <span class="badge bg-label-success">{{$order->status}}</span>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
            {{--            {{$transactions->appends()->links()}}--}}
        </div>
    </div>
@endsection
@section('vendor-script')
    @vite([
          ])
@endsection
@section('vendor-style')
    @vite([])
@endsection
