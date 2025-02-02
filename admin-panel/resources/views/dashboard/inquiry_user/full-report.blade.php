@extends('dashboard.layout.master')
@section('title', 'استعلام کاربر')
@section('content')

    <div class="row">
        <div class="col-lg-3 order-1 order-md-0">

            <div class="card mb-6">
                <div class="card-body pt-12">
                    <div class="user-avatar-section">
                        <div class=" d-flex align-items-center flex-column">
                            <img class="img-fluid rounded mb-4" src="{{ asset('images/avatars/avatar.webp') }}"
                                 height="120" width="120" alt="User avatar"/>
                            <div class="user-info text-center">
                                <h5>{{$user->fullname()}}</h5>
                            </div>
                        </div>
                    </div>

                    <h5 class="pb-4 border-bottom mb-4">جزئیات</h5>
                    <div class="info-container">
                        <ul class="list-unstyled mb-6">
                            <li class="mt-2 d-flex justify-content-between">
                                <span class="h6">نام کاربری:</span>
                                <span>{{ $user->username}}</span>
                            </li>

                            <li class="mt-2 d-flex justify-content-between">
                                <span class="h6">ایمیل:</span>
                                <span>{{$user->email}}</span>
                            </li>
                            <li class="mt-2 d-flex justify-content-between">
                                <span class="h6">وضعیت حساب:</span>
                                <span
                                    class="badge bg-label-{{\App\Enums\UserStatusEnum::TYPE_COLOR[$user->status->value]}} ms-1 align-self-baseline">{{$user->status->label()}}</span>
                            </li>
                            <li class="mt-2 d-flex justify-content-between">
                                <span class="h6">شماره تماس:</span>
                                <span>{{$user->mobile}}</span>
                            </li>
                            <li class="mt-2 d-flex justify-content-between">
                                <span class="h6">معرف:</span>
                                @if($user->introducerReferral)
                                    <span>{{$user->introducerReferral?->user->username}}</span>
                                @else
                                    <span>ندارد</span>
                                @endif

                            </li>
                            <li class="mt-2 d-flex justify-content-between">
                                <span class="h6">وضعیت حساب</span>

                                @if($user->activeFinancialBlocks->isEmpty())
                                    <span class="badge bg-label-success align-self-baseline">بدون محدودیت</span>
                                @else
                                    <div class="text-end">
                                        @foreach($user->activeFinancialBlocks as $block)
                                            <span
                                                class="badge bg-label-danger ms-1 align-self-baseline">    {{\App\Enums\UserFinancialBlockAction::TYPE_LABEL[$block->action] }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </li>
                            <li class="mt-2 d-flex justify-content-between">
                                <span class="h6">احراز هویت دو مرحله ای:</span>
                                <span
                                    class="text-{{auth('admin')->user()->twoFAStatus()?'success':'danger'}}">{{auth('admin')->user()->twoFAStatus()?'فعال':'غیرفعال'}}</span>
                            </li>
                            <li class="mt-2 d-flex justify-content-between">
                                <span class="h6">تاریح ایجاد حساب:</span>
                                <span>{{\App\Helpers\DateFormatter::convertToPersianDate($user->created_at,'H:i:s %Y-%m-%d')}}</span>

                            </li>
                            <li class="mt-2 d-flex justify-content-between">
                                <span class="h6">کشور:</span>
                                <span>{{$user->country}}</span>
                            </li>
                        </ul>
                        <div class="d-flex justify-content-center">
                            <a href="javascript:;" class="btn btn-label-danger suspend-user">تعلیق کاربر</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-9 col-lg-7 order-0 order-md-1">

            <div class="card  mt-4"></div>
        </div>

    </div>

    <div class="row mt-3">

        <div class="col">
            <div class="card mb-6">
                <div class="card-header px-0 pt-0">
                    <div class="nav-align-top">
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <button type="button" class="nav-link active" data-bs-toggle="tab"
                                        data-bs-target="#form-tabs-otc" aria-controls="form-tabs-personal"
                                        role="tab" aria-selected="true">
                                    <span class="ti ti-user ti-lg d-sm-none"></span>
                                    <span class="d-none d-sm-block">آخرین معاملات کاربر</span>
                                    <span class="badge badge-center rounded-pill bg-{{$totalOtcOrdersCount ? 'success':'danger'}} bg-glow ms-2">{{$totalOtcOrdersCount}}</span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab"
                                        data-bs-target="#form-tabs-withdrawal" aria-controls="form-tabs-account"
                                        role="tab" aria-selected="false">
                                    <span class="ti ti-user-cog ti-lg d-sm-none"></span>
                                    <span class="d-none d-sm-block">برداشت های اخیر کاربر</span>
                                    <span class="badge badge-center rounded-pill bg-{{$totalWithdrawsCount ? 'success':'danger'}} bg-glow ms-2">{{$totalWithdrawsCount}}</span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab"
                                        data-bs-target="#form-tabs-deposit" aria-controls="form-tabs-social"
                                        role="tab" aria-selected="false">
                                    <span class="ti ti-link ti-lg d-sm-none"></span>
                                    <span class="d-none d-sm-block">واریز های اخیر کاربر</span>
                                    <span class="badge badge-center rounded-pill bg-{{$totalDepositsCount ? 'success':'danger'}} bg-glow ms-2">{{$totalDepositsCount}}</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>


                <div class="tab-content p-0">
                    <div class="tab-pane fade active show" id="form-tabs-otc" role="tabpanel">

                        @if($otcOrders->isEmpty())
                            <p class="text-center h4">معامله ای یافت نشد🙄</p>
                        @else

                            <div class="table-responsive text-nowrap">
                                <table class="table table-striped">
                                    <thead>
                                    <tr>
                                        <th>زمان</th>
                                        <th>بازار</th>
                                        <th>نوع معامله</th>
                                        <th>مقدار</th>
                                        <th>قیمت</th>
                                        <th>ارزش</th>
                                        <th>کارمزد</th>
                                        <th>دریافتی</th>
                                        <th>وضعیت</th>
                                        <th>عملیات</th>
                                    </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                    @foreach($otcOrders as $order)
                                        <tr>
                                            <td class="font-number">
                                                {{\App\Helpers\DateFormatter::convertToPersianDate($order->created_at,'H:i:s %Y/%m/%d')}}
                                            </td>
                                            <td class="text-heading fw-medium">
                                                <img src="{{asset($order->market->baseCurrency->coinLogo())}}"
                                                     class="rounded-circle" width="32px">
                                                {{$order->market->name}}
                                            </td>
                                            <td>
                                                <span class="badge bg-label-{{$order->type->color()}}">{{$order->type->label()}}</span>
                                            </td>

                                            <td class="font-number" dir="ltr">
                                                        <span
                                                            class="ms-2">{{formatNumberTrimZeros($order->quantity)}}</span>
                                                <small>{{$order->market->baseCurrency->symbol}}</small>
                                            </td>
                                            <td class="font-number" dir="ltr">
                                                        <span
                                                            class="ms-2">{{formatNumberTrimZeros($order->price)}}</span>
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

                                            <td>
                                                <span class="badge bg-label-success">{{$order->status->label()}}</span>
                                            </td>
                                            <td>
                                                <a href="" class="btn btn-icon btn-text-secondary"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#otc-{{$order->id}}">
                                                    <i class="fa-light fa-memo-circle-info fa-xl"></i>
                                                </a>
                                                <div class="modal fade" id="otc-{{$order->id}}" tabindex="-1"
                                                     aria-model="true"
                                                     role="dialog">
                                                    <div class="modal-dialog modal-xl" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title font-number"
                                                                    id="exampleModalLabel4">
                                                                    تراکنش های
                                                                    معامله #{{$order->id}}</h5>
                                                                <button type="button" class="btn-close"
                                                                        data-bs-dismiss="modal"
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
                                                                                <td colspan="9"
                                                                                    class="text-center">تراکنشی
                                                                                    یافت
                                                                                    نشد.
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
                                                                                                <div
                                                                                                    class="avatar avatar-md ">
                                                                                                    <img
                                                                                                        src="{{asset($transaction->wallet->currency->coinLogo())}}"
                                                                                                        class="rounded-circle">
                                                                                                </div>
                                                                                                <div
                                                                                                    class="avatar avatar-md">
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
                                                                                    <td class="font-number"
                                                                                        dir="ltr">
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
                                                                <a type="button"
                                                                   href="{{route('admin.transaction.index',['otc_order_id'=>$order->id])}}"
                                                                   class="btn btn-primary">لیست تراکنش ها</a>
                                                                <button type="button"
                                                                        class="btn btn-label-secondary waves-effect"
                                                                        data-bs-dismiss="modal">بستن
                                                                </button>

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
                        @endif

                    </div>

                    <div class="tab-pane fade" id="form-tabs-withdrawal" role="tabpanel">
                        @if($withdraws->isEmpty())
                            <p class="text-center h4">برداشتی یافت نشد🙄</p>
                        @else
                            <div class="table-responsive text-nowrap">
                                <table class="table table-striped">
                                    <thead>
                                    <tr>
                                        <th>زمان درخواست</th>
                                        <th>Coin</th>
                                        <th>شبکه</th>
                                        <th>مقدار</th>
                                        <th>کارمزد برداشت <br> (فی شبکه + صرافی)</th>
                                        <th>آدرس</th>
                                        <th>(TxID) لینک تراکنش</th>
                                        <th>زمان تکمیل</th>
                                        <th>وضعیت</th>
                                        <th>عملیات</th>
                                    </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">

                                    @foreach($withdraws as $withdraw)
                                        <tr>
                                            <td class="font-number">
                                                {{\App\Helpers\DateFormatter::convertToPersianDate($withdraw->created_at,'H:i:s %Y/%m/%d')}}
                                            </td>
                                            <td class="text-heading fw-medium">
                                                <img src="{{asset($withdraw->currency->coinLogo())}}"
                                                     class="rounded-circle img-fluid" width="30">
                                                {{$withdraw->currency_symbol}}
                                            </td>
                                            <td>{{$withdraw->currencyChain->chain_name}}</td>
                                            <td class="font-number" dir="ltr">
                                                <h6 class="mb-0">{{formatNumberTrimZeros($withdraw->amount)}}</h6>
                                            </td>
                                            <td class="font-number" dir="ltr">
                                                <h6 class="mb-0">{{formatNumberTrimZeros($withdraw->total_fee)}}</h6>
                                            </td>
                                            <td class="font-number">
                                                <h6 class="mb-0">
                                                    @if($withdraw->explorer_address_url)
                                                        <a href="{{ $withdraw->explorer_address_url }}" target="_blank" class="me-1">
                                                            <i class="fa-regular fa-clone"></i>
                                                        </a>
                                                        <small>{{ shorten_hash($withdraw->address) }}</small>
                                                    @else
                                                        <span>N/A Address</span>
                                                    @endif
                                                </h6>
                                            </td>

                                            <td class="font-number">
                                                <h6 class="mb-0">
                                                    @if($withdraw->explorer_tx_url && $withdraw->transaction_hash)
                                                        <a href="{{ $withdraw->explorer_tx_url }}" target="_blank" class="me-1">
                                                            <i class="fa-regular fa-clone"></i>
                                                        </a>
                                                        <small>{{ shorten_hash($withdraw->transaction_hash) }}</small>
                                                    @else
                                                        <span>N/A TxID</span>
                                                    @endif
                                                </h6>
                                            </td>

                                            <td class="font-number">
                                                {{\App\Helpers\DateFormatter::convertToPersianDate($withdraw->created_at,'H:i:s %Y/%m/%d')}}
                                            </td>
                                            <td>
                                                <span class="badge bg-label-{{$withdraw->status->color()}}">{{$withdraw->status->label()}}</span>
                                            </td>
                                            <td>

                                                @if(in_array($withdraw->status, [
                                                        \App\Enums\WithdrawalStatusEnum::COMPLETED,
                                                        \App\Enums\WithdrawalStatusEnum::AWAITING_APPROVAL,
                                                        \App\Enums\WithdrawalStatusEnum::PENDING,
                                                        \App\Enums\WithdrawalStatusEnum::FAILED
                                                    ]))
                                                    <a href="#" class="btn btn-icon btn-text-secondary"
                                                       data-bs-toggle="modal"
                                                       data-bs-target="#withdraw-modal-{{$withdraw->id}}">
                                                        <i class="fa-regular fa-eye fa-xl"></i>
                                                    </a>

                                                    <div class="modal fade" id="withdraw-modal-{{$withdraw->id}}"
                                                         tabindex="-1"
                                                         aria-hidden="true">
                                                        <div class="modal-dialog" role="document">
                                                            <div class="modal-content border-3 border-{{
                                                    $withdraw->status === \App\Enums\WithdrawalStatusEnum::COMPLETED ? 'success' :
                                                    ($withdraw->status === \App\Enums\WithdrawalStatusEnum::FAILED ? 'danger' :
                                                        ($withdraw->status === \App\Enums\WithdrawalStatusEnum::PENDING ? 'warning':'info')
                                                    ) }}">
                                                                <div class="modal-header" dir="ltr">
                                                                    <h5 class="modal-title font-number">Withdraw
                                                                        #{{$withdraw->id}}</h5>
                                                                    <button type="button" class="btn-close"
                                                                            data-bs-dismiss="modal"
                                                                            aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    @include('dashboard.withdraw.withdrawal-modal-body', ['withdraw' => $withdraw])
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button"
                                                                            class="btn btn-label-secondary"
                                                                            data-bs-dismiss="modal">بستن
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if($withdraw->status === \App\Enums\WithdrawalStatusEnum::COMPLETED)
                                                    <a href="" class="btn btn-icon btn-text-secondary"
                                                       data-bs-toggle="modal"
                                                       data-bs-target="#withdraw-{{$withdraw->id}}">
                                                        <i class="fa-light fa-memo-circle-info fa-xl"></i>
                                                    </a>
                                                    <div class="modal fade" id="withdraw-{{$withdraw->id}}"
                                                         tabindex="-1"
                                                         aria-model="true"
                                                         role="dialog">
                                                        <div class="modal-dialog modal-xl" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title font-number"
                                                                        id="exampleModalLabel4">
                                                                        تراکنش
                                                                        های
                                                                        برداشت #{{$withdraw->id}}</h5>
                                                                    <button type="button" class="btn-close"
                                                                            data-bs-dismiss="modal"
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
                                                                            @if($withdraw->transactions->isEmpty())
                                                                                <tr>
                                                                                    <td colspan="9" class="text-center">
                                                                                        تراکنشی
                                                                                        یافت
                                                                                        نشد.
                                                                                    </td>
                                                                                </tr>
                                                                            @else

                                                                                @foreach($withdraw->transactions as $transaction)

                                                                                    <tr>
                                                                                        <td>{{$transaction->id}}</td>
                                                                                        <td class="text-heading fw-medium">
                                                                                            <div
                                                                                                class="d-flex justify-content-start align-items-center">
                                                                                                <div
                                                                                                    class="trans-avatar-group d-flex align-items-center assigned-avatar">
                                                                                                    <div
                                                                                                        class="avatar avatar-md ">
                                                                                                        <img
                                                                                                            src="{{asset($transaction->wallet->currency->coinLogo())}}"
                                                                                                            class="rounded-circle">
                                                                                                    </div>
                                                                                                    <div
                                                                                                        class="avatar avatar-md">
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
                                                                                        <td class="font-number"
                                                                                            dir="ltr">
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
                                                                    <a type="button"
                                                                       href="{{route('admin.transaction.index',['withdrawal_id'=>$withdraw->id])}}"
                                                                       class="btn btn-primary">لیست تراکنش ها</a>
                                                                    <button type="button"
                                                                            class="btn btn-label-secondary waves-effect"
                                                                            data-bs-dismiss="modal">بستن
                                                                    </button>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if($withdraw->status === \App\Enums\WithdrawalStatusEnum::AWAITING_APPROVAL)
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

                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    <div class="tab-pane fade" id="form-tabs-deposit" role="tabpanel">
                        @if($deposits->isEmpty())
                            <p class="text-center h4">واریزی یافت نشد🙄</p>
                        @else
                            <div class="table-responsive text-nowrap">
                                <table class="table table-striped">
                                    <thead>
                                    <tr>
                                        <th>شناسه</th>
                                        <th>Coin</th>
                                        <th>شبکه</th>
                                        <th>مقدار</th>
                                        <th>آدرس</th>
                                        <th>(TxID) لینک تراکنش</th>
                                        <th>زمان</th>
                                        <th>وضعیت</th>
                                        <th>عملیات</th>
                                    </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">

                                    @foreach($deposits as $deposit)
                                        <tr>
                                            <td>{{$deposit->id}}</td>

                                            <td class="text-heading fw-medium">
                                                <img src="{{asset($deposit->currency->coinLogo())}}"
                                                     class="rounded-circle img-fluid" width="30">
                                                {{$deposit->currency_symbol}}
                                            </td>
                                            <td>{{$deposit->currencyChain->chain_name}}</td>
                                            <td class="font-number" dir="ltr">
                                                <h6 class="mb-0">{{formatNumberTrimZeros($deposit->amount)}}</h6>
                                            </td>

                                            <td class="font-number">
                                                <h6 class="mb-0">
                                                    @if($deposit->explorer_address_url)
                                                        <a href="{{ $deposit->explorer_address_url }}"
                                                           target="_blank"
                                                           class="me-1">
                                                            <i class="fa-regular fa-clone"></i>
                                                        </a>
                                                        <small>{{ shorten_hash($deposit->address) }}</small>
                                                    @else
                                                        <span>N/A Address</span>
                                                    @endif
                                                </h6>
                                            </td>

                                            <td class="font-number">

                                                <h6 class="mb-0">
                                                    @if($deposit->explorer_tx_url && $deposit->transaction_hash)
                                                        <a href="{{ $deposit->explorer_tx_url }}" target="_blank"
                                                           class="me-1">
                                                            <i class="fa-regular fa-clone"></i>
                                                        </a>
                                                        <small>{{ shorten_hash($deposit->transaction_hash) }}</small>
                                                    @else
                                                        <span>N/A TxID</span>
                                                    @endif
                                                </h6>

                                            </td>
                                            <td class="font-number">
                                                {{\App\Helpers\DateFormatter::convertToPersianDate($deposit->created_at,'H:i:s %Y/%m/%d')}}
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-label-{{$deposit->status->color()}}">{{$deposit->status->label()}}</span>
                                            </td>
                                            <td>
                                                @if($deposit->status === \App\Enums\DepositStatusEnum::CONFIRMED)
                                                    <a href="" class="btn btn-icon btn-text-secondary"
                                                       data-bs-toggle="modal"
                                                       data-bs-target="#deposit-{{$deposit->id}}">
                                                        <i class="fa-regular fa-eye fa-xl"></i>
                                                    </a>
                                                    <div class="modal fade" id="deposit-{{$deposit->id}}"
                                                         tabindex="-1"
                                                         aria-model="true">
                                                        <div class="modal-dialog" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header" dir="ltr">
                                                                    <h5 class="modal-title font-number">Deposit
                                                                        #{{$deposit->id}}</h5>
                                                                    <button type="button" class="btn-close"
                                                                            data-bs-dismiss="modal"
                                                                            aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">

                                                                    <div
                                                                        class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom py-4 mb-4">
                                                                        <h6 class="m-0 mb-2 mb-md-0 me-12">شناسه
                                                                            تراکنش</h6>
                                                                        <div
                                                                            class="d-flex flex-wrap gap-4 font-number">
                                                                            Transaction
                                                                            #{{$deposit->transaction->id}}
                                                                        </div>
                                                                    </div>
                                                                    <div
                                                                        class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">
                                                                        <h6 class="m-0 mb-2 mb-md-0 me-12">موجودی
                                                                            کاربر
                                                                            قبل از
                                                                            واریز</h6>
                                                                        <div class="d-flex  gap-2 align-items-end">
                                                                            <small> {{$deposit->currency_symbol}}</small>
                                                                            <span
                                                                                class="font-number">{{formatNumberTrimZeros($deposit->transaction->balance - $deposit->transaction->amount)}}</span>

                                                                        </div>
                                                                    </div>
                                                                    <div
                                                                        class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">

                                                                        <h6 class="m-0 mb-2 mb-md-0 me-12">موجودی
                                                                            کاربر
                                                                            پس از واریز</h6>
                                                                        <div class="d-flex  gap-2 align-items-end">
                                                                            <small> {{$deposit->currency_symbol}}</small>
                                                                            <span
                                                                                class="font-number text-success">{{formatNumberTrimZeros($deposit->transaction->balance)}}</span>
                                                                        </div>
                                                                    </div>
                                                                    <div
                                                                        class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">

                                                                        <h6 class="m-0 mb-2 mb-md-0 me-12">توضیحات
                                                                            واریز</h6>
                                                                        <div class="text-wrap font-number w-60">
                                                                            {{$deposit->description}}
                                                                        </div>
                                                                    </div>
                                                                    <div
                                                                        class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">

                                                                        <h6 class="m-0 mb-2 mb-md-0 me-12">توضیحات
                                                                            تراکنش</h6>
                                                                        <div class="text-wrap font-number w-60">
                                                                            {{$deposit->transaction->description}}
                                                                        </div>
                                                                    </div>
                                                                    <div
                                                                        class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between border-bottom pb-4 mb-4">

                                                                        <h6 class="m-0 mb-2 mb-md-0 me-12">توضیحات
                                                                            ادمین</h6>
                                                                        <div class="text-wrap font-number">
                                                                            {{$deposit->transaction->admin_description}}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button"
                                                                            class="btn btn-label-secondary"
                                                                            data-bs-dismiss="modal">بستن
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>

    </div>

@endsection
