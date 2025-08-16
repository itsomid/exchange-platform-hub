@extends('dashboard.layout.master')
@section('title', 'ویرایش بازار')
@section('content')

    <div class="row g-6">
        <div class="col-xl-4 col-sm-6">
            <div class="card h-100">
                <div class="card-header pb-0">

                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-3 card-title">قیمت مرجع بازار ({{ $market->activeExchange->name }})</h5>
                        <div class="avatar-group d-flex align-items-center assigned-avatar">
                            <div class="me-8">{{ $market->base_currency }}/{{ $market->quote_currency }}</div>
                            <div class="avatar avatar-md">
                                <img src="{{ asset($market->quoteCurrency->coinLogo()) }}" class="rounded-circle ">
                            </div>
                            <div class="avatar avatar-md ">
                                <img src="{{ asset($market->baseCurrency->coinLogo()) }}" class="rounded-circle  ">
                            </div>

                        </div>
                    </div>
                    <div class="d-flex gap-2 align-items-center my-3 justify-content-end  font-number">
                        <div class="badge rounded bg-label-{{ $market->activeExchangePrice->price_change_percentage < 0 ? 'danger' : 'success' }}"
                            dir="ltr">
                            {{ $market->activeExchangePrice->price_change_percentage > 0 ? '+' : '' }}{{ formatNumber($market->activeExchangePrice->price_change_percentage) }}
                            %
                        </div>
                        <h2 class="mb-0">
                            ${{ formatNumberTrimZeros($market->activeExchangePrice->price) }}
                        </h2>

                    </div>
                </div>
                <div class="card-body px-0">
                    <div id="averageDailySales"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="card h-100">
                <div class="card-header pb-0">

                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-3 card-title">قیمت ارايه شده صرافی (فروش به مشتری)
                            <div class="badge rounded bg-label-{{ $market->activeExchangePrice->exchange_profit_sell < 0 ? 'danger' : 'success' }}"
                                dir="ltr">
                                {{ $market->activeExchangePrice->exchange_profit_sell > 0 ? '+' : '' }}{{ formatNumberTrimZeros($market->activeExchangePrice->exchange_profit_sell) }}
                                %
                            </div>
                        </h5>
                        <div class="avatar-group d-flex align-items-center assigned-avatar">
                            <div class="me-8">{{ $market->base_currency }}/{{ $market->quote_currency }}</div>
                            <div class="avatar avatar-md">
                                <img src="{{ asset($market->quoteCurrency->coinLogo()) }}" class="rounded-circle ">
                            </div>
                            <div class="avatar avatar-md">
                                <img src="{{ asset($market->baseCurrency->coinLogo()) }}" class="rounded-circle  ">
                            </div>

                        </div>
                    </div>


                    <div class="d-flex gap-2 align-items-center my-3 justify-content-end font-number">
                        <div class="badge rounded bg-label-{{ $market->price_change_percentage < 0 ? 'danger' : 'success' }}"
                            dir="ltr">
                            {{ $market->activeExchangePrice->price_change_percentage > 0 ? '+' : '' }}{{ formatNumber($market->activeExchangePrice->price_change_percentage, 2) }}
                            %
                        </div>
                        <h2 class="mb-0">
                            ${{ formatNumberTrimZeros($market->activeExchangePrice->exchange_sell_price) }}
                        </h2>

                    </div>
                </div>
                <div class="card-body px-0">
                    <div id="exchangePrice"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-sm-6">
            <div class="card h-100">
                <div class="card-header pb-0">

                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-3 card-title">قیمت ارايه شده صرافی (خرید از مشتری)
                            <div class="badge rounded bg-label-{{ $market->activeExchangePrice->exchange_profit_buy < 0 ? 'danger' : 'success' }}"
                                dir="ltr">
                                {{ $market->activeExchangePrice->exchange_profit_buy > 0 ? '+' : '' }}{{ formatNumberTrimZeros($market->activeExchangePrice->exchange_profit_buy) }}
                                %
                            </div>
                        </h5>
                        <div class="avatar-group d-flex align-items-center assigned-avatar">
                            <div class="me-8">{{ $market->base_currency }}/{{ $market->quote_currency }}</div>
                            <div class="avatar avatar-md">
                                <img src="{{ asset($market->quoteCurrency->coinLogo()) }}" class="rounded-circle ">
                            </div>
                            <div class="avatar avatar-md">
                                <img src="{{ asset($market->baseCurrency->coinLogo()) }}" class="rounded-circle  ">
                            </div>

                        </div>
                    </div>


                    <div class="d-flex gap-2 align-items-center my-3 justify-content-end font-number">
                        <div class="badge rounded bg-label-{{ $market->price_change_percentage < 0 ? 'danger' : 'success' }}"
                            dir="ltr">
                            {{ $market->activeExchangePrice->price_change_percentage > 0 ? '+' : '' }}{{ formatNumber($market->activeExchangePrice->price_change_percentage, 2) }}
                            %
                        </div>
                        <h2 class="mb-0">
                            ${{ formatNumberTrimZeros($market->activeExchangePrice->exchange_buy_price) }}
                        </h2>

                    </div>
                </div>
                <div class="card-body px-0">
                    <div id="exchangePrice"></div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <h4 class="card-header d-flex justify-content-between">
                    <div>
                        بازار{{ $market->baseCurrency->symbol }}/{{ $market->quoteCurrency->symbol }}
                    </div>


                </h4>
                <div class="card-body">

                    <form action="{{ route('admin.market.update', ['market' => $market]) }}" method="post">
                        @method('PATCH')
                        @csrf
                        <h6>اطلاعات بازار</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label" for="exchange">صرافی مرجع</label>
                                    <select name="exchange_id" id="exchange" class="select2 form-control">
                                        @foreach ($exchanges as $exchange)
                                            <option @if ($exchange->id === $market->activeExchangePrice->exchange_id) selected @endif
                                                value="{{ $exchange->id }}">{{ $exchange->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('exchange_id')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="w-100 mb-4"></div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="symbol">کوین پایه</label>
                                    <input name="symbol" id="symbol" class="form-control"
                                        value="{{ $market->baseCurrency->symbol }}" required disabled>
                                    @error('Symbol')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="symbol">ارز متقابل </label>
                                    <input name="symbol" id="symbol" class="form-control"
                                        value="{{ $market->quoteCurrency->symbol }}" required disabled>
                                    @error('Symbol')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row mt-5">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="min_otc_amount">حداقل مقدار معامله OTC در این
                                        بازار</label>
                                    <input name="min_otc_amount" id="min_otc_amount" class="form-control font-number"
                                        dir="ltr" placeholder="حداقل مقدار معامله OTC در این بازار"
                                        value="{{ formatNumberTrimZeros($market->min_otc_amount) }}" required>
                                    @error('min_otc_amount')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="max_otc_amount">حداکثر مقدار معامله OTC در این
                                        بازار</label>
                                    <input name="max_otc_amount" id="max_otc_amount" class="form-control font-number"
                                        dir="ltr" placeholder="حداکثر مقدار معامله  OTC در این بازار"
                                        value="{{ formatNumberTrimZeros($market->max_otc_amount) }}" required>
                                    @error('max_otc_amount')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row mt-5">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="min_trade_amount">حداقل مقدار معامله SPOT در این
                                        بازار</label>
                                    <input name="min_trade_amount" id="min_trade_amount" class="form-control font-number"
                                        dir="ltr" placeholder="حداقل مقدار معامله اسپات در این بازار"
                                        value="{{ formatNumberTrimZeros($market->min_trade_amount) }}" required>
                                    @error('min_trade_amount')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="max_trade_amount">حداکثر مقدار معامله SPOT در این
                                        بازار</label>
                                    <input name="max_trade_amount" id="max_trade_amount" class="form-control font-number"
                                        dir="ltr" placeholder="حداکثر مقدار معامله اسپات در این بازار"
                                        value="{{ formatNumberTrimZeros($market->max_trade_amount) }}" required>
                                    @error('max_trade_amount')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row mt-5">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="exchange_profit_sell">سود صرافی از محل خرید از صرافی
                                        مرجع (فروش به مشتری) (درصد)</label>
                                    <input name="exchange_profit_sell" id="exchange_profit_sell"
                                        class=" form-control font-number " dir="ltr"
                                        placeholder="سود صرافی از محل خرید از صرافی مرجع( فروش به مشتری)"
                                        value="{{ formatNumber($market->activeExchangePrice->exchange_profit_sell, 2) }}"
                                        required>
                                    @error('exchange_profit_sell')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="exchange_profit_buy">سود صرافی از محل فروش به صرافی
                                        مرجع (خرید از مشتری)</label>
                                    <input name="exchange_profit_buy" id="exchange_profit_buy"
                                        class=" form-control font-number " dir="ltr"
                                        placeholder="سود صرافی از محل فروش به صرافی مرجع (خرید از مشتری)"
                                        value="{{ formatNumber($market->activeExchangePrice->exchange_profit_buy, 2) }}"
                                        required>
                                    @error('exchange_profit_buy')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mt-5">
                                <label class="switch  switch-lg">
                                    <input type="checkbox" class="switch-input" name="is_active" value="1"
                                        {{ $market->is_active ? 'checked' : '' }} />
                                    <span class="switch-toggle-slider"></span>
                                    <span class="switch-label">وضعیت بازار (فعال/غیرفعال)</span>
                                </label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mt-5">
                                <label class="switch  switch-lg">
                                    <input type="checkbox" class="switch-input" name="price_update_enabled"
                                        value="1" {{ $market->price_update_enabled ? 'checked' : '' }} />
                                    <span class="switch-toggle-slider"></span>
                                    <span class="switch-label">بارگیری قیمت از صرافی مرجع (فعال/غیرفعال)

                                        <small class="text-muted">
                                            با فعال کردن این گزینه، قیمت بازار بعد از یک دقیقه شروع به بارگیری از صرافی مرجع
                                            می کند.
                                        </small>
                                    </span>

                                </label>
                            </div>
                        </div>

                        <div class=" d-flex justify-content-start mt-5">

                            <button class="btn btn-primary ">
                                <i class="fa fa-save mx-2"></i>
                                ذخیره
                            </button>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/js/config.js', 'resources/assets/js/market.js', 'resources/assets/vendor/js/forms-selects.js', 'resources/assets/vendor/libs/select2/select2.js'])

@endsection

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection
