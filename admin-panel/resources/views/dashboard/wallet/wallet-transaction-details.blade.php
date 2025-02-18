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
                            <th>کوین</th>
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
                            <th>ارزش</th>
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
                        @if( (isset($deposits) && $deposits->isNotEmpty()) )
                            @foreach($deposits as $deposit)
                                <tr>
                                    <td>{{ $deposit->id }}</td>
                                    <td class="text-heading fw-medium">
                                        <div class="avatar avatar-sm">
                                            <img src="{{ asset($deposit->currency->coinLogo()) }}"
                                                 class="rounded-circle">
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
                                    <td>
                                        {{ $deposit->description }}
                                    </td>
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

                        @elseif( (isset($withdrawals) && $withdrawals->isNotEmpty()) )
                            @foreach($withdrawals as $withdrawal)
                                <tr>
                                    <td>{{ $withdrawal->id }}</td>
                                    <td class="text-heading fw-medium">
                                        <div class="avatar avatar-sm">
                                            <img src="{{ asset($withdrawal->currency->coinLogo()) }}"
                                                 class="rounded-circle">
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
                                    <td>
                                        {{ $withdrawal->description }}
                                    </td>
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

                        @else
                            <tr>
                                <td colspan="9" class="text-center">تراکنشی موجود نیست</td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

@endsection


