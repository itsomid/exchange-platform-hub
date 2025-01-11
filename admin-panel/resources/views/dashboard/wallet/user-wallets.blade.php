@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول ها')
@section('content')
    <div class="row g-6">
        <div class="col-12">
            <div class="card mb-6">
                <div class="user-profile-header d-flex flex-column flex-lg-row text-sm-start text-center m-2">
                    <div class="d-flex align-items-center">
                        <img class="img-fluid rounded" src="http://127.0.0.1:8000/images/avatars/avatar.webp"
                             height="50" width="50" alt="User avatar">
                    </div>
                    <div class="flex-grow-1">
                        <div
                            class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-4">
                            <div class="user-profile-info">
                                <h4 class="mb-2">{{$user->fullname()}}</h4>
                                <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4 my-2">
                                    <li class="list-inline-item d-flex gap-1 align-items-center">
                                        <i class="fa-regular fa-hashtag"></i>
                                        <span class="font-number">{{$user->id}}</span>
                                    </li>
                                    <li class="list-inline-item d-flex gap-2 align-items-center">
                                        <i class="fa-regular fa-user-check"></i>
                                        <span class="text-body">{{$user->username}}</span>
                                    </li>
                                    <li class="list-inline-item d-flex gap-2 align-items-center">
                                        <i class="fa-regular fa-envelope"></i>
                                        <span class="text-body">{{$user->email}}</span>
                                    </li>
                                    <li class="list-inline-item d-flex gap-2 align-items-center">
                                        <i class="fa-regular fa-clock"></i>
                                        <span class="text-body">
                                            @if($user->latestActiveToken())
                                                {{\App\Helpers\DateFormatter::convertToPersianDate($user->latestActiveToken()->last_used_at,'H:i:s %Y/%m/%d')}}
                                            @else
                                                <span>فعالیتی نداشته است</span>
                                            @endif
                                        </span>
                                    </li>

                                </ul>
                            </div>

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
                    </div>
                    <div class="dropdown">
                        <button
                            class="btn btn-text-secondary rounded-pill text-muted border-0 p-2 me-n1 "
                            type="button" id="earningReportsId" data-bs-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false">
                            <i class="fa-regular fa-grip-dots-vertical ti-md text-muted"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="earningReportsId">
                            <a class="dropdown-item " href="javascript:void(0);">View More</a>
                            <a class="dropdown-item " href="javascript:void(0);">Delete</a>
                        </div>
                    </div>
                    <!-- </div> -->
                </div>
                <div class="card-body">
                    <div class="row align-items-center g-md-8">
                        <div class="col-12 col-md-4 d-flex flex-column">
                            <div class="d-flex gap-2 align-items-center mb-3 flex-wrap">

                                <h2 class="mb-0 font-number">{{formatNumber($totalAssetsValue,2)}}
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
                                    <h6 class="mb-0 fw-normal">موجودی مسدود شده (به USDT)</h6>
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
                        <h3 class="mt-4 mb-1 font-number">{{formatNumberTrimZeros($wallet->balance)}}
                            <span class="text-muted h4">{{$wallet->currency_symbol}}</span>
                        </h3>
                        <p class="mb-5">

                            <small class="text-muted fw-light">USDT</small>
                            <span class="text-primary me-2 font-number">{{formatNumber($wallet->assetValue,2)}}</span>

                        </p>
                        @if($wallet->locked_balance !=0)
                            <p class="mb-0">
                                <small class="text-danger">موجودی مسدود شده:</small>
                                <small
                                    class="text-danger fw-bold ms-2 font-number">{{formatNumberTrimZeros($wallet->locked_balance)}}
                                    <span class="text-danger ">{{$wallet->currency_symbol}}</span>
                                </small>
                            </p>
                        @endif

                    </div>
                    <div class="card-body border-top">
                        <a href="{{route('admin.wallet.detail',['user'=>$user->id,'wallet'=>$wallet->id,'type'=>'deposit'])}}"
                           class="btn btn-label-primary me-2">مشاهده جزئیات</a>
                        <a class="btn btn-icon btn-primary"
                           href="{{route('admin.wallet.increase-credit.form',['currency'=>$wallet->currency_symbol , 'user'=>$user])}}">
                            <i class="fa-regular fa-plus fa-xl"></i>
                        </a>
                        <a class="btn btn-icon btn-danger ms-2"
                           href="{{route('admin.wallet.block-balance.form',['wallet'=>$wallet , 'user'=>$user])}}">
                            <i class="fa-regular fa-ban fa-xl"></i>
                        </a>
                        <a class="btn btn-icon btn-success ms-2"
                           href="{{route('admin.wallet.unblock-balance.form',['wallet'=>$wallet , 'user'=>$user])}}">
                            <i class="fa-regular fa-lock-open fa-xl"></i>
                        </a>

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
