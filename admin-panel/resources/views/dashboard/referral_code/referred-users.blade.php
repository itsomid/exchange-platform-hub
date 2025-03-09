@extends('dashboard.layout.master')
@section('title', 'افراد ثبت نام شده')
@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="content-left">
                            <h5 class="mb-1">{{$referralCode->registered_users_count}}</h5>
                            <small>تعداد افراد ثبت نام شده با کد معرف</small>
                        </div>
                        <span class="badge bg-label-danger rounded-circle p-3">
                            <i class="fa-light fa-users"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="content-left">
                            <h5 class="mb-1">
                                <small>USDT</small>
                                {{formatNumberTrimZeros($referralCode->transactions_sum_amount)}}
                            </h5>
                            <small>مجموع درآمد کد معرف برای سازنده و افراد معرفی شده </small>
                        </div>
                        <span class="badge bg-label-success rounded-circle p-3">
                            <i class="fa-light fa-gift"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="content-left">
                            <h5 class="mb-1">{{formatNumber($conversationRate,2)}}%</h5>
                            <small>نرخ تبدیل</small>
                        </div>
                        <span class="badge bg-label-success rounded-circle p-3">
                            <i class="fa-light fa-infinity "></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">

        <div class="card-body">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">افراد دعوت شده با کد معرف
                    <a class="btn btn-primary font-number p-1" data-bs-html='true'>
                        {{ $referralCode->code}}
                    </a>
                </h5>

            </div>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>REFERRED USER ID</th>
                        <th>کاربر</th>
                        <th>تعداد معاملات</th>
                        <th>مجموع دریافتی سازنده کد (USDT) </th>
                        <th>مجموع دریافتی فرد معرفی شده (USDT) </th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @foreach($referralCode->registeredUsers as $referredUser)
                        <tr>
                            <td>
                                {{$referredUser->id}}
                            </td>

                            <td>

                                <div class="d-flex flex-column">
                                    <a href="" class="text-heading text-truncate">
                                        <span class="fw-medium">{{ $referredUser->email}}</span>
                                    </a>
                                    <small>{{ $referredUser->fullname()}}</small>
                                </div>
                            </td>

                            <td>
                                <span class="me-2">{{count($referredUser->introducerReferralCodeUsage)}}</span>
                            </td>

                            <td class="font-number">
                                {{$referredUser->introducerReferralCodeUsage->sum('transaction.amount')}}
                            </td>
                            <td class="font-number">
                                {{$referredUser->friendsReferralCodeUsage->sum('transaction.amount')}}
                            </td>
                            <td>
                                <div class="d-flex align-items-center">

                                    <a class="text-secondary me-3" href="{{route('admin.referral_code.showTransactionsForReferredUser',['user'=>$referredUser->id])}}">

                                        <i class="fa-light fa-eye fa-lg"></i>
                                    </a>

                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>

@endsection
