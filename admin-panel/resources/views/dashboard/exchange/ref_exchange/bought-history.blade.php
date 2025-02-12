@extends('dashboard.layout.master')
@section('title', 'مدیریت کدهای معرف')
@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="content-left">
                            <h5 class="mb-1">{{count($transactions)}}</h5>
                            <small>تعداد خریدها از صرافی مرجع</small>
                        </div>
                        <span class="badge bg-label-danger rounded-circle p-3">
                            <i class="fa-light fa-users fa-xl"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="card">

        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست خرید از صرافی مرجع</h5>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>شماره سفارش</th>
                        <th>بازار</th>
                        <th>Side</th>
                        <th>مقدار</th>
                        <th>فی</th>
                        <th>میانگین قیمت (USDT)</th>
                        <th>زمان ایجاد</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @foreach($transactions as $transaction)
                        <tr>
                            <td>{{$transaction->id}}</td>
                            <td>{{$transaction->order_id}}</td>
                            <td class="text-heading ">
                                <div class="d-flex justify-content-start align-items-center">
                                    <div class="avatar-group d-flex align-items-center assigned-avatar">
                                        <div class="avatar avatar-sm">
                                            <img
                                                src="{{asset($transaction->exchangeMarket->quoteCurrency->coinLogo())}}"
                                                class="" width="10">
                                        </div>
                                        <div class="avatar avatar-sm ">
                                            <img src="{{asset($transaction->exchangeMarket->baseCurrency->coinLogo())}}"
                                                 class="" >
                                        </div>
                                    </div>
                                    <div class="ms-3">{{$transaction->market}}</div>
                                </div>
                            </td>
                            <td>{{$transaction->side}}</td>
                            <td class="font-number">{{$transaction->amount}} ({{$transaction->currency->symbol}})</td>
                            <td class="font-number">{{$transaction->fee}} (CET)</td>
                            <td class="font-number">{{$transaction->response->data->last_fill_price}}</td>
                            <td>{{$transaction->created_at}}</td>
                            <td>
                                <a href="" class="btn btn-icon btn-text-secondary" data-bs-toggle="modal"
                                   data-bs-target="#transaction-{{$transaction->id}}">
                                    <i class="fa-light fa-memo-circle-info fa-xl"></i>
                                </a>
                                <div class="modal fade" id="transaction-{{$transaction->id}}" tabindex="-1"
                                     aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header" dir="ltr">
                                                <h5 class="modal-title font-number">معامله#{{$transaction->id}}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">تاریخ معمامله در صرافی مرجع</h6>
                                                    <span
                                                        class="font-number text-primary">{{$transaction->formattedCreatedAt}}</span>
                                                </div>

                                                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">ارزش به دلار</h6>
                                                    <span
                                                        class="font-number text-primary">{{formatNumberTrimZeros($transaction->response->data->filled_value)}}
                                                        <small>USDT</small>
                                                    </span>
                                                </div>
                                                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">taker fee rate</h6>
                                                    <span
                                                        class="font-number text-primary">{{formatNumberTrimZeros($transaction->response->data->taker_fee_rate)}}</span>
                                                </div>
                                                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                                                    <h6 class="m-0 mb-2 mb-md-0 me-12">unfilled amount</h6>
                                                    <span
                                                        class="font-number text-primary">{{formatNumberTrimZeros($transaction->response->data->unfilled_amount)}}</span>
                                                </div>

                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">بستن</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{--                @include('dashboard.layout.pagination', ['collection' => $regentCodes])--}}
        </div>
    </div>

@endsection

@section('vendor-script')

@endsection
