@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول های صرافی')
@section('content')


    {{-- EXCHANGE LOCAL WALLET--}}
    <div class="card mt-6">
        <div class="card-body">
            <h4 class="mb-0">دارایی Hot Wallet</h4>
        </div>
    </div>
    <div class="row g-6 mt-3">
        @foreach($exchangeHotWallets as $wallet)
            <div class="col-lg-3 col-sm-6">
                <div class="card card-border-shadow-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center ">
                            <div class="avatar me-4">
                                <div class="avatar flex-shrink-0 me-4">
                                    <img src="{{$wallet->wallet->currency->coinLogo()}}" class="img-fluid" width="50px">
                                </div>
                            </div>
                            <h4 class="mb-0">کیف پول {{$wallet->wallet->currency_symbol}}</h4>
                        </div>
                        <h3 class="mt-4 mb-1 font-number">{{$wallet->hot_balance['amount']}}
                            <span class="text-muted h4">{{$wallet->wallet->currency_symbol}}</span>
                        </h3>

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
