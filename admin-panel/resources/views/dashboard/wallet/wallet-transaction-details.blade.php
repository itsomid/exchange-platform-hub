@extends('dashboard.wallet.layout.wallet-detail-master')
@section('title', 'مدیریت کیف پول')
@section('wallet-details-body')

    <div class="row mt-6">
        <div class="col-12">
            <div class="card">
                <h5 class="card-header">تاریخچه {{$transactionTitle}}</h5>
                <div class="table-responsive text-nowrap">
                    @if(isset($deposits) && $deposits->isNotEmpty() || isset($withdrawals) && $withdrawals->isNotEmpty())
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>شناسه</th>
                                <th>کوین</th>
                                <th>کاربر</th>
                                <th>رمز ارز</th>
                                <th>مقدار</th>
                                <th>ارزش</th>
                                <th>توضیحات</th>
                                <th>تاریخ و زمان</th>
                                <th>عملیات</th>
                            </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                            @if(isset($deposits) && $deposits->isNotEmpty())
                                @foreach($deposits as $deposit)
                                    <tr>
                                        <td>{{ $deposit->id }}</td>
                                        <td class="text-heading fw-medium">
                                            <div class="avatar avatar-sm">
                                                @if($deposit->currency)
                                                    <img src="{{ asset($deposit->currency->coinLogo()) }}"
                                                         class="rounded-circle">
                                                @else
                                                    <span class="avatar-initial rounded-circle bg-label-secondary">{{ substr($deposit->currency_symbol, 0, 2) }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <a href="" class="text-heading text-truncate">
                                                    <span class="fw-medium">{{ $deposit->user->email }}</span>
                                                </a>
                                                <small>{{ $deposit->user->username }}</small>
                                            </div>
                                        </td>
                                        <td>{{ $deposit->currency_symbol }}</td>
                                        <td class="font-number">
                                            <h6>{{ formatNumber($deposit->amount) }}</h6>
                                        </td>
                                        <td class="font-number">
                                            <h6>{{ formatNumber($deposit->usdt_value) }}</h6>
                                        </td>
                                        <td>{{ $deposit->description }}</td>
                                        <td class="font-number">
                                            {{ \App\Helpers\DateFormatter::convertToPersianDate($deposit->created_at, 'H:i:s %Y/%m/%d') }}
                                        </td>
                                        <td>
                                            <a href=""
                                               class="btn btn-{{ \App\Enums\DepositStatusEnum::TYPE_COLOR[$deposit->status->value] }} btn-sm">
                                                {{ $deposit->status->label() }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif

                            @if(isset($withdrawals) && $withdrawals->isNotEmpty())
                                @foreach($withdrawals as $withdrawal)
                                    <tr>
                                        <td>{{ $withdrawal->id }}</td>
                                        <td class="text-heading fw-medium">
                                            <div class="avatar avatar-sm">
                                                @if($withdrawal->currency)
                                                    <img src="{{ asset($withdrawal->currency->coinLogo()) }}"
                                                         class="rounded-circle">
                                                @else
                                                    <span class="avatar-initial rounded-circle bg-label-secondary">{{ substr($withdrawal->currency_symbol, 0, 2) }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <a href="" class="text-heading text-truncate">
                                                    <span class="fw-medium">{{ $withdrawal->user->email }}</span>
                                                </a>
                                                <small>{{ $withdrawal->user->username }}</small>
                                            </div>
                                        </td>
                                        <td>{{ $withdrawal->currency_symbol }}</td>
                                        <td class="font-number">
                                            <h6>{{ formatNumber($withdrawal->amount) }}</h6>
                                        </td>
                                        <td class="font-number">
                                            <h6>{{ formatNumber($withdrawal->usdt_value) }}</h6>
                                        </td>
                                        <td>{{ $withdrawal->description }}</td>
                                        <td class="font-number">
                                            {{ \App\Helpers\DateFormatter::convertToPersianDate($withdrawal->created_at, 'H:i:s %Y/%m/%d') }}
                                        </td>
                                        <td>
                                            <a href=""
                                               class="btn btn-{{ \App\Enums\WithdrawalStatusEnum::TYPE_COLOR[$withdrawal->status->value] }} btn-sm">
                                                {{ $withdrawal->status->label() }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                    @elseif(isset($otcBuy) && $otcBuy->isNotEmpty() || isset($otcSell) && $otcSell->isNotEmpty())
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>شناسه</th>
                                <th>بازار</th>
                                <th>نوع</th>
                                <th>مقدار</th>
                                <th>قیمت واحد</th>
                                <th>مبلغ کل</th>
                                <th>کارمزد</th>
                                <th>دریافتی</th>
                                <th>تاریخ و زمان</th>
                                <th>وضعیت</th>
                            </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                @if(isset($otcBuy) && $otcBuy->isNotEmpty())

                                @foreach($otcBuy as $order)
                                    <tr class="table-{{$order->status->color()}}">
                                        <td>{{$order->id}}</td>
                                        <td class="text-heading fw-medium">
                                            <img src="{{asset($order->market->baseCurrency->coinLogo())}}"
                                                 class="rounded-circle" width="32px">
                                            <small>  {{$order->market->name}}</small>

                                        </td>
                                        <td>
                                            <span class="badge bg-label-{{$order->type->color()}}">{{$order->type->label()}}</span>
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
                                            {{formatNumberTrimZeros($order->total_value)}}
                                            <small>USDT</small>
                                        </td>
                                        <td class="font-number" dir="ltr">
                                            {{formatNumberTrimZeros($order->fee)}}

                                            <small>{{$order->type === \App\Enums\OTCOrderTypeEnum::BUY ? $order->market->baseCurrency->symbol : $order->market->quoteCurrency->symbol}}</small>
                                        </td>
                                        <td class="font-number" dir="ltr">
                                            @if($order->type === \App\Enums\OTCOrderTypeEnum::BUY)
                                                {{formatNumberTrimZeros(bcsub($order->quantity ,  $order->fee,8))}}
                                                <small>{{$order->market->baseCurrency->symbol}}</small>
                                            @else
                                                {{formatNumberTrimZeros(bcsub(bcmul($order->price , $order->quantity,5) ,  $order->fee,5))}}
                                                <small>{{$order->market->quoteCurrency->symbol}}</small>
                                            @endif
                                        </td>
                                        <td class="font-number">
                                            {{\App\Helpers\DateFormatter::convertToPersianDate($order->created_at,'H:i:s %Y/%m/%d')}}
                                        </td>

                                        <td>
                                            <span class="badge bg-{{$order->status->color()}}">{{$order->status->label()}}</span>
                                        </td>

                                    </tr>
                                @endforeach

                            @endif
                            @if(isset($otcSell) && $otcSell->isNotEmpty())
                                @foreach($otcSell as $order)
                                    <tr class="table-{{$order->status->color()}}">
                                        <td>{{$order->id}}</td>
                                        <td class="text-heading fw-medium">
                                            <img src="{{asset($order->market->baseCurrency->coinLogo())}}"
                                                 class="rounded-circle" width="32px">
                                            <small>  {{$order->market->name}}</small>

                                        </td>
                                        <td>
                                            <span class="badge bg-label-{{$order->type->color()}}">{{$order->type->label()}}</span>
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
                                            {{formatNumberTrimZeros($order->total_value)}}
                                            <small>USDT</small>
                                        </td>
                                        <td class="font-number" dir="ltr">
                                            {{formatNumberTrimZeros($order->fee)}}

                                            <small>{{$order->type === \App\Enums\OTCOrderTypeEnum::BUY ? $order->market->baseCurrency->symbol : $order->market->quoteCurrency->symbol}}</small>
                                        </td>
                                        <td class="font-number" dir="ltr">
                                            @if($order->type === \App\Enums\OTCOrderTypeEnum::BUY)
                                                {{formatNumberTrimZeros(bcsub($order->quantity ,  $order->fee,8))}}
                                                <small>{{$order->market->baseCurrency->symbol}}</small>
                                            @else
                                                {{formatNumberTrimZeros(bcsub(bcmul($order->price , $order->quantity,5) ,  $order->fee,5))}}
                                                <small>{{$order->market->quoteCurrency->symbol}}</small>
                                            @endif
                                        </td>
                                        <td class="font-number">
                                            {{\App\Helpers\DateFormatter::convertToPersianDate($order->created_at,'H:i:s %Y/%m/%d')}}
                                        </td>

                                        <td>
                                            <span class="badge bg-{{$order->status->color()}}">{{$order->status->label()}}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                    @elseif(isset($lockedBalanceDetails) && $lockedBalanceDetails->isNotEmpty())
                            <table class="table table-hover">
                                <thead>
                            <tr>
                                <th>میزان</th>
                                <th>نوع</th>
                                <th>تاریخ شروع</th>
                                <th>تاریخ پایان</th>
                                <th>توضیحات</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($lockedBalanceDetails as $detail)
                                <tr class="{{$detail->type->value !== 'admin' ? ($detail->deleted_at ? 'table-danger' : 'table-success') : ''}}">
                                    <td class="font-number">{{formatNumberTrimZeros($detail->amount)}}</td>
                                    <td>
                                        <span class="badge bg-{{$detail->type->color()}}">{{$detail->type->label()}}</span>
                                    </td>
                                    <td>{{App\Helpers\DateFormatter::convertToPersianDate($detail->created_at,'H:i:s %Y/%m/%d')}}</td>
                                    @if($detail->deleted_at)
                                        <td>{{App\Helpers\DateFormatter::convertToPersianDate($detail->deleted_at,'H:i:s %Y/%m/%d')}}</td>
                                    @else
                                        <td>-</td>
                                    @endif
                                    <td>{{$detail->description}}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center p-4">
                            <p class="text-muted">هیچ تراکنشی یافت نشد</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection


