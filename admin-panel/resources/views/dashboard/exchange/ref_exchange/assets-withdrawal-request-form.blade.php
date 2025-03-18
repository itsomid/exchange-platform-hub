@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول ها')
@section('content')

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div class="col-md-7 mb-md-0 mb-6 ps-0 d-flex align-items-center">
                <img src="{{$currency->coinLogo()}}" width="60px">
                <h5 class="mb-0 ms-3 card-title">فرم برداشت ({{$currency->name}}) از Coinex</h5>
            </div>

        </div>
        <div class="card-body">
            <form action="{{route('admin.ref-exchange.assets-gathering-to-hd-wallet.store')}}" method="post">
                @csrf
                <div class="row">

                    <div class="col-xl-4 mb-4">
                        <div class="form-group">
                            <label class="form-label" for="currency_symbol">کوین مورد نظر:</label>
                            <input type="text" name="currency_symbol" id="currency_symbol" class="form-control"
                                   value="{{$currency->symbol}}" placeholder="نام کوین" readonly>

                            @error('currency')<small class="text-danger">{{$message}}</small>@enderror
                        </div>
                    </div>
                    <div class="col-xl-4 mb-4">
                        <div class="form-group">
                            <label class="form-label" for="currency_chain">شبکه مورد نظر را انتخاب کنید:</label>
                            <select id="currency_chain" class="form-select" name="currency_chain"
                                    data-placeholder="لطفا شبکه  مورد نظر را انتخاب کنید.">
                                @foreach($currencyChains as $chain)
                                    <option value="{{$chain->chain}}">{{$chain->chain}} - حداقل
                                        برداشت {{formatNumberTrimZeros($chain->min_withdraw_amount)}}</option>
                                @endforeach
                            </select>
                            @error('currency')<small class="text-danger">{{$message}}</small>@enderror
                        </div>
                    </div>
                    <div class="w-100"></div>
                    @foreach($walletChains as $chain)
                        <div class="col-xl-4 mb-3">
                            <label for="withdrawal_address" class="form-label">
                                <span>آدرس برداشت</span>
                                <span class="mx-2">({{$chain->currency_chain}})</span>
                            </label>
                            <input type="text" name="withdrawal_address" id="withdrawal_address" class="form-control"
                                   value="{{$chain->address}}" placeholder="آدرس برداشت">
                        </div>
                        <div class="w-100"></div>
                    @endforeach

                    <div class=" d-flex justify-content-start mt-5">

                        <button class="btn btn-primary ">
                            <i class="fa fa-save mx-2"></i>
                            برداشت از صرافی مرجع
                        </button>

                    </div>
                </div>
            </form>
        </div>
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
