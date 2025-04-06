@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول ها')
@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>تعداد کل در خواست های برداشت</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$withdrawals->count()}}</h4>
                            </div>
                            <span>برداشت های در انتظار تکمیل</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{$pendingWithdrawals->count()}}</h4>
                            </div>
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="fa-solid fa-arrow-up-right"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <div class="card-title mb-0">
                        <h5 class="mb-1">مجموع برداشت های در انتظار تکمیل</h5>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-text-secondary rounded-pill text-muted border-0 p-2 me-n1" type="button"
                                id="MonthlyCampaign" data-bs-toggle="dropdown" aria-haspopup="true"
                                aria-expanded="false">
                            <i class="fa-regular fa-grip-dots-vertical text-muted"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="MonthlyCampaign">
                            <a class="dropdown-item" href="javascript:void(0);">امروز</a>
                            <a class="dropdown-item" href="javascript:void(0);">ماه</a>
                            <a class="dropdown-item" href="javascript:void(0);">سال</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="p-0 m-0">

                        @forelse($pendingWithdrawals as $withdraw)
                            <li class="mb-6 d-flex justify-content-between align-items-center">

                                <img src="{{asset($withdraw->currency->coinLogo())}}" class="img-fluid" width="45px">

                                <div class="d-flex justify-content-between w-100 flex-wrap">
                                    <h6 class="mb-0 ms-4">{{$withdraw->currency->symbol}}</h6>
                                    <div class="d-flex ">
                                        <small class="me-2 align-self-end">{{$withdraw->currency->symbol}}</small>
                                        <h5 class="mb-0 font-number">{{formatNumberTrimZeros($withdraw->total_withdraw_amount)}}</h5>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <p class="text-right">برداشتی ثبت نشده است</p>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>


    <div class="card">
        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">لیست درخواست های تجمیع در انتظار تکمیل</h5>

            </div>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>کوین</th>
                        <th>تراکنش</th>
                        <th>مقدار</th>
                        <th>تاریخ شروع</th>
                        <th>تاریخ تکمیل</th>
                        <th>وضعیت</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @foreach($withdrawals as $withdraw)

                        <tr>
                            <td>{{$withdraw->id}}</td>
                            <td class="text-heading fw-medium">
                                <img src="{{asset($withdraw->currency->coinLogo())}}"
                                     class="rounded-circle img-fluid" width="30">
                                {{$withdraw->currency->name}}
                            </td>
                            <td class="font-number">Transaction #{{$withdraw->transaction->id}}</td>
                            <td class="font-number">{{$withdraw->transaction->amount}}</td>
                            <td class="font-number">
                                {{\App\Helpers\DateFormatter::convertToPersianDate($withdraw->created_at,'H:i:s %Y/%m/%d')}}
                            </td>
                            <td class="font-number">
                                @if($withdraw->status === \App\Enums\OTCRefExchangeWithdrawalStatusEnum::COMPLETED || $withdraw->status === \App\Enums\OTCRefExchangeWithdrawalStatusEnum::CANCELLED)
                                    {{\App\Helpers\DateFormatter::convertToPersianDate($withdraw->updated_at,'H:i:s %Y/%m/%d')}}
                                @else
                                    در انتظار تکمیل
                                @endif

                            </td>
                            <td>
                                <span
                                    class="badge bg-label-{{$withdraw->status->color()}} align-self-baseline">{{$withdraw->status->label()}}</span>
                            </td>

                            {{--                            <td>--}}
                            {{--                                {{$withdraw->description}}--}}
                            {{--                            </td>--}}

                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
