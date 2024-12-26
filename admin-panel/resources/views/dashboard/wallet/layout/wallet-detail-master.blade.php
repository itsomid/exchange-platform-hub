@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول')
@section('content')

    <div class="row">
        <div class="col-12 mb-6">

            <div class="user-profile-header d-flex flex-column flex-lg-row text-sm-start text-center m-2">
                <div class="d-flex align-items-center">
                    <img src="{{$wallet->currency->coinLogo()}}" class="img-fluid" width="50px">
                </div>
                <div class="flex-grow-1">
                    <div
                        class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-4">
                        <div class="user-profile-info">
                            <h4 class="mb-0">کیف پول {{$wallet->currency->name}}</h4>


                            <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4 my-2">
                                <li class="list-inline-item d-flex gap-1 align-items-center">
                                    <i class="fa-regular fa-hashtag"></i>
                                    <span class="font-number">{{$user->id}}</span>
                                </li>
                                <li class="list-inline-item d-flex gap-2 align-items-center">
                                    <i class="fa-regular fa-user-check"></i>
                                    <small class="text-body">{{$user->username}}</small>
                                </li>
                                <li class="list-inline-item d-flex gap-2 align-items-center">
                                    <i class="fa-regular fa-envelope"></i>
                                    <small class="text-body">{{$user->email}}</small>
                                </li>
                                <li class="list-inline-item d-flex gap-2 align-items-center">
                                    <i class="fa-regular fa-clock"></i>
                                    <small class="text-body">آخرین فعالیت در ۳ مهر ۱۴۰۳</small>
                                </li>

                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card mb-6">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between">

                        <div class="d-flex flex-column justify-content-center">
                            <h5 class="mb-1">موجودی کیف پول</h5>
                            <h3 class="text-primary mt-4 mb-1 font-number">{{formatNumberTrimZeros($wallet->balance)}}
                                <span class=" h5">{{$wallet->currency_symbol}}</span>
                            </h3>
                            <p class="mb-5">
                                <small class="text-muted fw-light">USDT</small>
                                <span class="h5  me-2 font-number">{{formatNumber($specificAssetValue,2)}}</span>
                            </p>

                        </div>
                        <div class="d-flex align-content-center flex-wrap gap-4">
                            <div class="d-flex gap-4">
                                <a href="{{route('admin.wallet.increase-credit.form',['user'=>$user,'currency'=>$wallet->currency_symbol])}}" class="btn btn-info">
                                    <i class="fa-regular fa-plus me-1"></i> افزایش موجودی
                                </a>
                                <a href="{{route('admin.wallet.block-balance.form',['wallet'=>$wallet,'user'=>$user])}}" class="btn btn-google-plus">
                                    <i class="fa-regular fa-ban me-1"></i> مسدود سازی موجودی
                                </a>
                                <a href="{{route('admin.wallet.block-balance.form',['wallet'=>$wallet,'user'=>$user])}}" class="btn btn-success">
                                    <i class="fa-regular fa-lock-open me-1"></i> آزاد سازی موجودی
                                </a>
                            </div>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="card card-border-shadow-success h-100">
                <div class="card-body">

                    <div class=" d-flex justify-content-between align-items-center ">
                        <h5 class="fw-bold text-black">مجموع واریزی</h5>
                        <div class="avatar me-4">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="fa-solid fa-arrow-down-left fa-xl"></i>
                            </span>
                        </div>
                    </div>
                    <h3 class="mt-4 mb-1">{{formatNumberTrimZeros($totalDeposits)}}
                        <span class="text-muted h4">{{$wallet->currency_symbol}}</span>
                    </h3>
                    <p class="mb-5">
                        <small class="text-muted fw-light">USDT</small>
                        <span class="text-muted  me-2">{{formatNumber($totalDepositsValue,2)}}</span>
                    </p>
                    <p class="mb-0">

                        <small class="text-muted">آخرین واریزی
                            <span class="text-secondary fw-bold">{{$lastDepositDate}}</span>
                        </small>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card card-border-shadow-danger h-100">
                <div class="card-body">

                    <div class=" d-flex justify-content-between align-items-center ">
                        <h5 class="fw-bold text-black">مجموع برداشت</h5>
                        <div class="avatar me-4">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="fa-solid fa-arrow-up-right fa-xl"></i>
                            </span>
                        </div>
                    </div>
                    <h3 class="mt-4 mb-1">{{formatNumberTrimZeros($totalWithdraws)}}
                        <span class="text-muted h4">{{$wallet->currency_symbol}}</span>
                    </h3>
                    <p class="mb-5">
                        <small class="text-muted fw-light">USDT</small>
                        <span class="text-muted  me-2">{{formatNumber($specificAssetValue,2)}}</span>
                    </p>
                    <p class="mb-0">

                        <small class="text-muted">آخرین برداشت:
                            <span class="text-secondary fw-bold">{{$lastWithdrawDate}}</span>
                        </small>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card card-border-shadow-primary h-100">
                <div class="card-body">

                    <div class=" d-flex justify-content-between align-items-center ">
                        <h5 class="fw-bold text-black">مجموع فروش OTC</h5>
                        <div class="avatar me-4">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="fa-solid fa-chart-mixed-up-circle-dollar fa-xl"></i>

                            </span>
                        </div>
                    </div>
                    <h3 class="mt-4 mb-1">{{formatNumberTrimZeros($totalOtcSell)}}
                        <span class="text-muted h4">{{$wallet->currency_symbol}}</span>
                    </h3>
                    <p class="mb-5">
                        <small class="text-muted fw-light">USDT</small>
                        <span class="text-muted  me-2">{{formatNumber($totalOtcSellValue,2)}}</span>
                    </p>
                    <p class="mb-0">
                        <small class="text-muted">آخرین معامله:
                            <span class="text-secondary fw-bold">{{$lastOtcSellDate}}</span>
                        </small>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card card-border-shadow-primary h-100">
                <div class="card-body">

                    <div class=" d-flex justify-content-between align-items-center ">
                        <h5 class="fw-bold text-black">مجموع خرید OTC</h5>
                        <div class="avatar me-4">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="fa-solid fa-chart-mixed-up-circle-dollar fa-xl"></i>

                            </span>
                        </div>
                    </div>
                    <h3 class="mt-4 mb-1">{{formatNumberTrimZeros($totalOtcBuy)}}
                        <span class="text-muted h4">{{$wallet->currency_symbol}}</span>
                    </h3>
                    <p class="mb-5">
                        <small class="text-muted fw-light">USDT</small>
                        <span class="text-muted  me-2">{{formatNumber($totalOtcBuyValue,2)}}</span>
                    </p>
                    <p class="mb-0">

                        <small class="text-muted">آخرین معامله
                            <span class="text-secondary fw-bold">{{$lastOtcBuyDate}}</span>

                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-12 mt-6">

        <!-- User Pills -->
        <ul class="nav nav-pills flex-column flex-md-row mb-4">
            <li class="nav-item">
                <a class="nav-link @if(request()->route('type') == 'deposit') active @endif"
                   href="{{route('admin.wallet.detail',['user' => $user->id,'wallet'=>$wallet->id,'type'=>'deposit'])}}">
                    <i class="fa-regular fa-arrow-down-to-bracket me-2"></i>
                    واریز
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link @if(request()->route('type') == 'withdrawal') active @endif"
                   href="{{route('admin.wallet.detail',['user' => $user->id,'wallet'=>$wallet->id,'type'=>'withdrawal'])}}">
                    <i class="fa-regular fa-arrow-up-from-bracket me-2"></i>
                    برداشت
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link @if(request()->route('type') == 'buy') active @endif"
                   href="{{route('admin.wallet.detail',['user' => $user->id,'wallet'=>$wallet->id,'type'=>'buy'])}}">
                    <i class="fa-solid fa-swap me-2"></i>
                    خرید OTC
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link @if(request()->route('type') == 'sell') active @endif"
                   href="{{route('admin.wallet.detail',['user' => $user->id,'wallet'=>$wallet->id, 'type'=>'sell'])}}">
                    <i class="fa-solid fa-swap me-2"></i>
                    فروش OTC
                </a>
            </li>
        </ul>
        <!--/ User Pills -->
        @yield('wallet-details-body')
    </div>

@endsection
@section('vendor-script')
    @vite([
            'resources/assets/vendor/libs/apex-charts/apexcharts.js',
             'resources/assets/js/config.js',
            'resources/assets/js/wallet.js'
         ])
@endsection

@section('vendor-style')
    @vite([
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
])
@endsection
