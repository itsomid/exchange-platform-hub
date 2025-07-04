@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول های صرافی')
@section('content')

    {{-- EXCHANGE LOCAL WALLET--}}
    <div class="card mt-6">
        <div class="card-body">
            <h4 class="mb-0">دارایی کیف پول های صرافی</h4>
        </div>
    </div>
    <div class="row g-6 mt-3">
        @foreach($exchangeWallets as $wallet)
            <div class="col-lg-3 col-sm-6">
                <div class="card card-border-shadow-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center ">
                            <div class="avatar me-4">
                                <div class="avatar flex-shrink-0 me-4">
                                    <img src="{{$wallet->currency->coinLogo()}}" class="img-fluid" width="50px">
                                </div>
                            </div>
                            <h4 class="mb-0">کیف پول {{$wallet->currency->name}}</h4>
                        </div>
                        <h3 class="mt-4 mb-1 font-number">{{formatNumberTrimZeros($wallet->balance)}}
                            <span class="text-muted h4">{{$wallet->currency_symbol}}</span>
                        </h3>
                        <p class="mb-2">
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
                        <a href="{{route('admin.wallet.detail',['user'=> config('bitexroom.user_id'),'wallet'=>$wallet->id,'type'=>'deposit'])}}"
                           class="btn btn-label-primary me-2">مشاهده جزئیات</a>
                        <a class="btn btn-icon btn-primary"
                           href="{{route('admin.wallet.increase-credit.form',['currency'=>$wallet->currency_symbol , 'user'=>config('bitexroom.user_id')])}}">
                            <i class="fa-regular fa-plus fa-xl"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach

    </div>


    {{-- END OF EXCHANGE LOCAL WALLET--}}
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
