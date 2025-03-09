@extends('dashboard.layout.master')
@section('title', 'افراد ثبت نام شده')
@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="content-left">
                            <h5 class="mb-1">{{count($introducerReferralCodeUsage)}}</h5>
                            <small>
                                تعداد تراکنش های ثبت شده برای سازنده کد معرف از کاربر
                            </small>
                        </div>
                        <span class="badge bg-label-danger rounded-circle p-3">
                            <i class="fa-light fa-users fa-xl"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="content-left">
                            <h5 class="mb-1">
                                <small>USDT</small>
                                {{formatNumberTrimZeros($introducerReferralCodeUsage->sum('transaction.amount'))}}</h5>
                            <small>مجموع درآمد سازنده کد معرف از کاربر</small>
                        </div>
                        <span class="badge bg-label-success rounded-circle p-3">
                            <i class="fa-light fa-gift fa-xl"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="content-left">
                            <h5 class="mb-1">{{count($friendReferralCodeUsage?? 0)}}</h5>
                            <small>
                                تعداد تراکنش های ثبت شده برای استفاده کننده از کد معرف
                                <br>
                                ({{$user->email}})
                            </small>
                        </div>
                        <span class="badge bg-label-danger rounded-circle p-3">
                            <i class="fa-light fa-users fa-xl"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="content-left">
                            <h5 class="mb-1">
                                <small>USDT</small>
                                {{formatNumberTrimZeros($friendReferralCodeUsage->sum('transaction.amount'))}}</h5>
                            <small>مجموع درآمد استفاده کننده از کد معرف
                                <br>
                                ({{$user->email}})
                            </small>
                        </div>
                        <span class="badge bg-label-success rounded-circle p-3">
                            <i class="fa-light fa-gift fa-xl"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">

        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">تراکنش های ثبت شده برای سازنده کد معرف (%{{$user->introducerReferral->introducer_fee}}) </h5>

            </div>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>مقدار</th>
                        <th>تاریخ و زمان</th>
                        <th>توضیحات</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @foreach($introducerReferralCodeUsage as $usage)
                        <tr>
                            <td>
                                {{$usage->transaction->id}}
                            </td>

                            <td>
                                <small>USDT</small>
                                {{formatNumberTrimZeros($usage->transaction->amount)}}
                            </td>

                            <td>
                                {{\App\Helpers\DateFormatter::convertToPersianDate($usage->transaction->created_at,'H:i:s %Y-%m-%d')}}
                            </td>

                            <td>
                                {{$usage->transaction->description}}
                            </td>

                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
    <div class="card">

        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">تراکنش های ثبت شده برای استفاده کننده از کد معرف
                    ({{$user->email}})
                    (%{{$user->introducerReferral->friend_fee}}) </h5>

            </div>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>مقدار</th>
                        <th>تاریخ و زمان</th>
                        <th>توضیحات</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @foreach($friendReferralCodeUsage as $usage)
                        <tr>
                            <td>
                                {{$usage->transaction->id}}
                            </td>

                            <td>
                                <small>USDT</small>
                                {{formatNumberTrimZeros($usage->transaction->amount)}}
                            </td>

                            <td>
                                {{\App\Helpers\DateFormatter::convertToPersianDate($usage->transaction->created_at,'H:i:s %Y-%m-%d')}}
                            </td>

                            <td>
                                {{$usage->transaction->description}}
                            </td>

                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
@endsection
