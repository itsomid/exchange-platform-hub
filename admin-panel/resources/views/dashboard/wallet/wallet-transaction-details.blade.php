@extends('dashboard.wallet.layout.wallet-detail-master')
@section('title', 'مدیریت کیف پول')
@section('wallet-details-body')

    <div class="row mt-6">
        <div class="col-12">
            <div class="card">
                <h5 class="card-header">تاریخچه {{$transactionTitle}}</h5>
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>شناسه</th>
                            <th>نوع تراکنش</th>
                            <th>کاربر</th>
                            <th>رمز ارز</th>
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
                            <th>مقدار موجودی</th>
                            <th>توضیحات</th>
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
                            <th>عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                        @if($transactions->isEmpty())
                            <tr>
                                <td colspan="9" class="text-center">تراکنشی موجود نیست</td>
                            </tr>
                        @else
                            @foreach($transactions as $transaction)
                                <tr>
                                    <td>{{$transaction->id}}</td>
                                    <td class="text-heading fw-medium">
                                        <div class="d-flex justify-content-start align-items-center">
                                            <div class="trans-avatar-group d-flex align-items-center assigned-avatar">

                                                <div class="avatar avatar-md ">
                                                    <img src="{{asset($transaction->wallet->currency->coinLogo())}}"
                                                         class="rounded-circle  ">
                                                </div>
                                                <div class="avatar avatar-md">
                                                <span
                                                    class="avatar-initial rounded-circle bg-label-{{\App\Enums\TransactionTypeEnum::TYPE_COLOR[$transaction->type->value]}}">
                                                    <i class="fa-regular fa-{{\App\Enums\TransactionTypeEnum::TYPE_ICON[$transaction->type->value]}} mx-3"></i>
                                                </span>
                                                </div>
                                            </div>

                                            <span
                                                class="badge bg-label-{{\App\Enums\TransactionTypeEnum::TYPE_COLOR[$transaction->type->value]}} ms-2">
                                           {{$transaction->type->value}}
                                        </span>
                                        </div>

                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <a href="" class="text-heading text-truncate">
                                                <span class="fw-medium">{{$transaction->user->email}}</span>
                                            </a>
                                            <small>{{$transaction->user->username}}</small>
                                        </div>
                                    </td>

                                    <td>{{$transaction->wallet->currency_symbol}}</td>
                                    <td class="font-number">
                                        <h6>{{formatNumber($transaction->amount)}}</h6>
                                    </td>
                                    <td class="font-number">
                                        <h6>{{formatNumber($transaction->balance)}}</h6>
                                    </td>
                                    <td>
                                        {{$transaction->description}}
                                    </td>
                                    <td class="font-number">
                                        {{\App\Helpers\DateFormatter::convertToPersianDate($transaction->created_at,'H:i:s %Y/%m/%d')}}
                                    </td>

                                    <td>
                                        @if($transaction->deposit)
                                            <a href=""
                                               class="btn btn-{{\App\Enums\DepositStatusEnum::TYPE_COLOR[$transaction->deposit->deposit_type->value]}} btn-sm">
                                                {{\App\Enums\DepositStatusEnum::TYPE_LABEL[$transaction->deposit->deposit_type->value]}}
                                            </a>
                                        @endif

                                    </td>
                                </tr>

                            @endforeach
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

@endsection


