@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول ها')
@section('content')
    <div class="row g-6">
        <div class="col-12">
            <div class="card mb-6">
                <div class="user-profile-header d-flex flex-column flex-lg-row text-sm-start text-center m-2">
                    <div class="d-flex align-items-center">
                        <img class="img-fluid rounded" src="http://127.0.0.1:8000/images/avatars/avatar.webp" height="50" width="50" alt="User avatar">
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-4">
                            <div class="user-profile-info">
                                <h4 class="mb-2">{{$user->fullname()}}</h4>
                                <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4 my-2">
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
                            <a href="javascript:void(0)" class="btn btn-primary mb-1 waves-effect waves-light">
                                <i class="ti ti-user-check ti-xs me-2"></i>Connected
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card h-100">
                <div class="card-header pb-0 d-flex justify-content-between">
                    <div class="card-title mb-0">
                        <h5 class="mb-0 card-title">ارزش کل موجودی‌ها به USDT</h5>
                        {{--                        <p class="card-subtitle">Weekly Earnings Overview</p>--}}
                    </div>
                    <div class="dropdown">
                        <button
                            class="btn btn-text-secondary rounded-pill text-muted border-0 p-2 me-n1 waves-effect waves-light"
                            type="button" id="earningReportsId" data-bs-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false">
                            <i class="fa-regular fa-grip-dots-vertical ti-md text-muted"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="earningReportsId">
                            <a class="dropdown-item waves-effect" href="javascript:void(0);">View More</a>
                            <a class="dropdown-item waves-effect" href="javascript:void(0);">Delete</a>
                        </div>
                    </div>
                    <!-- </div> -->
                </div>
                <div class="card-body">
                    <div class="row align-items-center g-md-8">
                        <div class="col-12 col-md-4 d-flex flex-column">
                            <div class="d-flex gap-2 align-items-center mb-3 flex-wrap">

                                <h2 class="mb-0">{{number_format($totalAssetsValue,2)}}
                                    <small class="text-muted fw-light">USDT</small>
                                </h2>
                                <div class="badge rounded bg-label-success">+4.2%</div>
                            </div>

                        </div>
                        <div class="col-12 col-md-8 ps-xl-8">
                            <div id="projectStatusChart">

                            </div>
                        </div>
                    </div>
                    <div class="border rounded p-5 mt-5">
                        <div class="row gap-4 gap-sm-0">
                            <div class="col-12 col-sm-4">
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="badge rounded bg-label-success p-2">
                                        <i class="fa-regular fa-wallet"></i>
                                    </div>
                                    <h6 class="mb-0 fw-normal">موجودی در دسترس</h6>
                                </div>
                                <h4 class="my-2">$545.69</h4>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="badge rounded bg-label-info p-2">
                                        <i class="fa-regular fa-hourglass-clock"></i>
                                    </div>
                                    <h6 class="mb-0 fw-normal">موجودی در سفارش</h6>
                                </div>
                                <h4 class="my-2">$256.34</h4>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="badge rounded bg-label-danger p-2">
                                        <i class="fa-regular fa-ban"></i>
                                    </div>
                                    <h6 class="mb-0 fw-normal">موجودی مسدود شده</h6>
                                </div>
                                <h4 class="my-2">$74.19</h4>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="badge rounded bg-label-danger p-2">
                                        <i class="fa-solid fa-pallet-boxes"></i>
                                    </div>
                                    <h6 class="mb-0 fw-normal">تعداد کیف پول</h6>
                                </div>
                                <h4 class="my-2">5</h4>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="badge rounded bg-label-primary p-2">
                                        <i class="fa-regular fa-money-from-bracket"></i>
                                    </div>
                                    <h6 class="mb-0 fw-normal">تعداد تراکنش ها</h6>
                                </div>
                                <h4 class="my-2">28</h4>
                            </div>
                            <div class="col-12 col-sm-4">
                                <div class="d-flex gap-2 align-items-center">
                                    <div class="badge rounded bg-label-primary p-2">
                                        <i class="fa-regular fa-chart-line-up-down"></i>
                                    </div>
                                    <h6 class="mb-0 fw-normal">سود و زیان دیروز</h6>
                                </div>
                                <h4 class="my-2">28</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-6 mt-3">
        @foreach($wallets as $wallet)
            <div class="col-lg-3 col-sm-6">
                <div class="card card-border-shadow-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-4">
                                <div class="avatar flex-shrink-0 me-4">
                                    <img src="{{$wallet->currency->coinLogo()}}" class="img-fluid" width="50px">
                                    {{--                                <img src="http://127.0.0.1:8000/images/coins/btc.svg" class="img-fluid" width="50px">--}}
                                </div>
                            </div>
                            <h4 class="mb-0">کیف پول {{$wallet->currency->name}}</h4>
                        </div>
                        <h3 class="mt-4 mb-1">{{$wallet->balance}} <span class="text-muted h4">{{$wallet->currency_symbol}}</span></h3>
                        <p class="mb-5">
                            <small class="text-muted fw-light">USDT</small>
                            <span class="text-muted  me-2">103,305.27</span>
                        </p>
                        <p class="mb-0">
                            <small class="text-danger">موجودی مسدود شده:</small>
                            <small class="text-danger fw-bold ms-2">103,305.27 <span class="text-danger ">USDT</span></small>
                        </p>
                    </div>
                    <div class="card-body border-top">
                        <button type="button" class="btn btn-primary me-2">مشاهده جزئیات</button>
                        <button type="button" class="btn btn-danger waves-effect waves-light">مسدود کردن</button>
                    </div>
                </div>
            </div>
        @endforeach

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
