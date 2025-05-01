@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول های صرافی')
@section('content')

    <div class="card">
        <div class="card-body">
            <h4 class="mb-0">دارایی در صرافی مرجع (Coinex)</h4>
        </div>
    </div>

    <div class="row g-6 mt-1">
        @foreach($supportCoinexAssets as $asset)
            <div class="col-lg-3 col-sm-6">
                <div class="card card-border-shadow-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-4">
                                <div class="avatar flex-shrink-0 me-4">
                                    <img src="{{$asset->coinLogo}}" class="img-fluid" width="50px">
                                </div>
                            </div>
                            <h4 class="mb-0">کیف پول {{$asset->ccy}}</h4>
                        </div>
                        <h3 class="mt-4 mb-1 font-number">{{formatNumberTrimZeros($asset->available)}}
                            <span class="text-muted h4">{{$asset->ccy}}</span>
                        </h3>

                        <p class="mb-0">
                            <small class="text-danger">موجودی مسدود شده:</small>
                            <small class="text-danger fw-bold ms-2 font-number">{{$asset->frozen}}</small>
                        </p>

                    </div>
                    <div class="card-body border-top">
                        @if(\App\Models\Currency::whereSymbol($asset->ccy)->exists())
                            @if(\App\Models\Currency::whereSymbol($asset->ccy)->first()->chains->isNotEmpty())
                                <a href="{{route('admin.ref-exchange.assets-gathering-to-hd-wallet.create',['currency_symbol'=>$asset->ccy,'amount'=>$asset->available])}}"
                                   class="btn btn-primary">
                                    <i class="fa-regular fa-arrow-up-right fa-xl mx-2"></i>
                                    برداشت دارایی
                                </a>
                            @else
                                امکان برداشت موجود نیست
                            @endif
                        @endif

                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="card mt-5">
        <div class="card-body">
            <h4 class="mb-0">دارایی های پشتیبانی نشده در صرافی مرجع (Coinex)</h4>
        </div>
    </div>

    <div class="row g-6 mt-2">
        @foreach($unsupportCoinexAssets as $asset)
            <div class="col-lg-3 col-sm-6">
                <div class="card card-border-shadow-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-4">
                                <div class="avatar flex-shrink-0 me-4">
                                    <img src="{{$asset->coinLogo}}" class="img-fluid" width="50px">
                                </div>
                            </div>
                            <h4 class="mb-0">کیف پول {{$asset->ccy}}</h4>
                        </div>
                        <h3 class="mt-4 mb-1 font-number">{{formatNumberTrimZeros($asset->available)}}
                            <span class="text-muted h4">{{$asset->ccy}}</span>
                        </h3>

                        <p class="mb-0">
                            <small class="text-danger">موجودی مسدود شده:</small>
                            <small class="text-danger fw-bold ms-2 font-number">{{$asset->frozen}}</small>
                        </p>

                    </div>
                    <div class="card-body border-top">
                        @if(\App\Models\Currency::whereSymbol($asset->ccy)->exists())
                            @if(\App\Models\Currency::whereSymbol($asset->ccy)->first()->chains->isNotEmpty())
                                <a href="{{route('admin.ref-exchange.assets-gathering-to-hd-wallet.create',['currency_symbol'=>$asset->ccy,'amount'=>$asset->available])}}"
                                   class="btn btn-primary">
                                    <i class="fa-regular fa-arrow-up-right fa-xl mx-2"></i>
                                    برداشت دارایی
                                </a>
                            @else
                                امکان برداشت موجود نیست
                            @endif
                        @endif

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
