@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول های صرافی')
@section('content')

    {{-- EXCHANGE LOCAL WALLET--}}
    <div class="card mt-6">
        <div class="card-header">
            <div class="d-flex justify-content-between">
                <h4 class="mb-0">دارایی Hot Wallet</h4>
                <a href="{{route('admin.wallet.assets-gathering-to-cold-wallet')}}" class="btn btn-primary disabled">انتقال دارایی به Cold Wallet</a>
            </div>
        </div>
    </div>
    <div class="row g-6 mt-3">


        @foreach ($exchangeHotWallets as $walletChain)
            <div class="col-lg-3 col-sm-6">
                <div class="card card-action card-border-shadow-success" data-wallet-chain-id="{{ $walletChain->id }}">
                    <div class="card-alert"></div>
                    <div class="card-header">
                        <h5 class="card-action-title mb-0">
                            <div class="d-flex align-items-center">
                                <div class="avatar me-4">
                                    <div class="avatar flex-shrink-0 me-4">
                                        <img src="{{$walletChain->wallet->currency->coinLogo()}}" class="img-fluid"
                                             width="50px">
                                    </div>
                                </div>
                                <h4 class="mb-0">کیف پول {{ $walletChain->wallet->currency_symbol }}
                                    ({{$walletChain->currency_chain}})</h4>
                            </div>
                        </h5>
                        <div class="card-action-element">
                            <ul class="list-inline mb-0">
                                <li class="list-inline-item">
                                    <a href="javascript:void(0);" class="card-reload d-flex">
                                        <i class="fa fa-refresh"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <h3 class="mt-2 mb-4 ">
                            <span
                                class="font-number hot-balance">{{formatNumberTrimZeros($balances[$walletChain->wallet->currency_symbol][$walletChain->currency_chain])}}</span>
                            <span class="text-muted h6 me-3">{{ $walletChain->wallet->currency_symbol }}</span>
                        </h3>
                        <div class="mb-0 d-flex flex-column align-items-end">
                            <a href="{{$walletChain->explorer_address_url}}" target="_blank"
                               class="text-primary font-number fw-bolder text-decoration-underline">
                                {{ $walletChain->address }}
                                <i class="fa-regular fa-clone"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

    </div>

@endsection
@section('vendor-style')
    @vite([
      'resources/assets/vendor/libs/spinkit/spinkit.scss'
    ])
@endsection
@section('vendor-script')
    @vite([
      'resources/assets/vendor/libs/block-ui/block-ui.js',

          'resources/assets/js/cards-actions.js'
    ])
@endsection

