@extends('dashboard.layout.master')
@section('title', 'مدیریت کیف پول ها')
@section('content')

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div class="col-md-7 mb-md-0 mb-6 ps-0 d-flex align-items-center">
                <img src="{{$currency->coinLogo()}}" width="60px">
                <h5 class="mb-0 ms-3 card-title">فرم برداشت ({{$currency->name}}) از {{$exchangeName}}</h5>
            </div>
            <div class="col-md-5 col-8 pe-0 ps-0 ps-md-2">

                <dl class="row mb-0 gx-4">

           
                        <dt class="col-sm-5 mb-2 d-md-flex align-items-center justify-content-end">
                            <span class="fw-bold text-primary ">موجودی</span>
                        </dt>
                        <dd class="col-sm-7">
                            <div class="input-group">

                                <input type="text" class="form-control font-number fw-bold" readonly="readonly"
                                       dir="ltr"
                                       value="{{formatNumberTrimZeros($exchangeBalance->getAvailable())}}">
                                <span class="input-group-text text-primary fw-bold "> {{$exchangeBalance->getCcy()}}</span>
                            </div>
                        </dd>
                

                </dl>
            </div>
        </div>
        <div class="card-body">
            <form action="{{route('admin.ref-exchange.assets-gathering-to-hd-wallet.store')}}" method="post">
                @csrf
                <div class="row">

                    <div class="col-xl-4 mb-4">
                        <div class="form-group">
                            <label class="form-label" for="exchange_slug">صرافی مرجع:</label>
                            <select id="exchange_slug" class="form-select" name="exchange_slug"
                                    data-placeholder="لطفا صرافی مرجع را انتخاب کنید."
                                    onchange="updateExchange()">
                                @foreach($exchanges as $exchange)
                                    <option value="{{$exchange->slug}}" 
                                        {{$selectedExchange == $exchange->slug ? 'selected' : ''}}>
                                        {{$exchange->name}}
                                    </option>
                                @endforeach
                            </select>
                            @error('exchange_slug')<small class="text-danger">{{$message}}</small>@enderror
                        </div>
                    </div>
                    <div class="w-100"></div>
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
                    <div class="col-xl-4 mb-4">
                        <div class="form-group">
                            <label class="form-label" for="amount">مقدار را وارد کنید:</label>
                            <div class="input-group">
                                <input type="text" name="amount" id="amount" class="form-control" placeholder="مقدار">
                                <button type="button" class="btn btn-outline-primary" onclick="setMaxAmount()">ALL</button>
                            </div>
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


@push('scripts')
<script>
function setMaxAmount() {
    const maxAmount = "{{formatNumberTrimZeros($exchangeBalance->getAvailable())}}";
    document.getElementById('amount').value = maxAmount;
}

function updateExchange() {
    const selectedExchange = document.getElementById('exchange_slug').value;
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('exchange', selectedExchange);
    currentUrl.searchParams.set('currency_symbol', '{{$currency->symbol}}');
    window.location.href = currentUrl.toString();
}
</script>
@endpush
