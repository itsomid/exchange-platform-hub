@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول ها')
@section('content')




    <div class="card">
        <div class="card-body">
            <form action="{{route('wallets.assets-gathering-to-hd-wallet.store')}}" method="post">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="form-group">
                            <label class="form-label" for="currency">کوین مورد نظر را انتخاب کنید:</label>
                            <select id="currency" class="form-select" name="currency_symbol"
                                    data-placeholder="لطفا کوین  مورد نظر را انتخاب کنید.">
                                @foreach($currencies as $currency)
                                    <option
                                        {{$selectedCurrency === $currency->symbol ? 'selected' : ''}} value="{{$currency->symbol}}">{{$currency->name}}</option>
                                @endforeach
                            </select>
                            @error('currency')<small class="text-danger">{{$message}}</small>@enderror
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="form-group">
                            <label class="form-label" for="chain">شبکه مورد نظر را انتخاب کنید:</label>
                            <select id="chain" class="form-select" name="currency_chain"
                                    data-placeholder="لطفا شبکه  مورد نظر را انتخاب کنید.">
                                @foreach($currencyChains as $chain)
                                    <option value="{{$chain->chain}}">{{$chain->chain}}</option>
                                @endforeach
                            </select>
                            @error('currency')<small class="text-danger">{{$message}}</small>@enderror
                        </div>
                    </div>

                    <div class=" d-flex justify-content-start mt-5">
                        <div class="col-md-1">
                            <button class="btn btn-primary ">
                                <i class="fa fa-save mx-2"></i>
                                برداشت از صرافی مرجع
                            </button>
                        </div>
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
