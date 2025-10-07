@extends('dashboard.layout.master')
@section('title', 'تنظیمات و پیکربندی')
@section('content')
    <div class="row g-6">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="card-title header-elements">
                        <h5 class="m-0 me-2">کارمزد خرید و فروش OTC</h5>

                    </div>
                    <form action="{{ route('admin.setting.int.update-otc-commission') }}" method="post">
                        @csrf
                        <div class="row mt-5">
                            <div class="col-xl-4">
                                <div class="form-group">
                                    <label class="form-label" for="otc_buy_fee">کارمزد فروش به مشتری (درصد)</label>
                                    <input name="otc_buy_fee" id="otc_buy_fee" class="form-control"
                                        placeholder="کارمزد فروش به مشتری (درصد)" value="{{ $otcBuyFee->value }}" required>
                                    @error('otcBuyFee')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-xl-4">
                                <div class="form-group">
                                    <label class="form-label" for="otc_sell_fee">کارمزد خرید از مشتری (درصد)</label>
                                    <input name="otc_sell_fee" id="otc_sell_fee" class="form-control"
                                        placeholder="کارمزد خرید از مشتری (درصد)" value="{{ $otcSellFee->value }}" required>
                                    @error('otcSellFee')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class=" d-flex justify-content-start mt-5">

                                <button class="btn btn-primary ">
                                    <i class="fa fa-save mx-2"></i>
                                    ذخیره
                                </button>

                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>


        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="card-title header-elements">
                        <h5 class="m-0 me-2">کارمزد Taker و Maker در فروش اسپات</h5>

                    </div>
                    <form action="{{ route('admin.setting.int.update-spot-commission') }}" method="post">
                        @csrf
                        <div class="row mt-5">
                            <div class="col-xl-4">
                                <div class="form-group">
                                    <label class="form-label" for="spot_maker_fee">کارمزد Maker (درصد)</label>
                                    <input name="spot_maker_fee" id="spot_maker_fee" class="form-control"
                                        placeholder="کارمزد Maker (درصد)" value="{{ $spotMakerFee->value }}" required>
                                    @error('spotMakerFee')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-xl-4">
                                <div class="form-group">
                                    <label class="form-label" for="spot_taker_fee">کارمزد Taker (درصد)</label>
                                    <input name="spot_taker_fee" id="spot_taker_fee" class="form-control"
                                        placeholder="کارمزد Taker (درصد)" value="{{ $spotTakerFee->value }}" required>
                                    @error('spotTakerFee')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class=" d-flex justify-content-start mt-5">

                                <button class="btn btn-primary ">
                                    <i class="fa fa-save mx-2"></i>
                                    ذخیره
                                </button>

                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="card-title header-elements">
                        <h5 class="m-0 me-2">تنظیمات دعوت از دوستان

                            <span
                                class="text-{{ $referralProfitStatus->value ? 'success' : 'danger' }}">({{ $referralProfitStatus->value ? 'فعال' : 'غیرفعال' }})</span>
                        </h5>
                    </div>
                    <form action="{{ route('admin.setting.int.update-referral-setting') }}" method="post">
                        @csrf
                        <div class="row mt-5">
                            <div class="col-xl-4">
                                <div class="form-group">
                                    <label class="form-label" for="referral_profit_percentage">حداکثر درصد اهدایی به کاربران
                                        برای معرفی دوستان</label>
                                    <input type="number" name="referral_profit_percentage" id="referral_profit_percentage"
                                        class="form-control" placeholder="درصد اهدایی به کاربران برای معرفی دوستان"
                                        value="{{ $referralProfitPercentage->value }}" required>
                                    @error('otcBuyFee')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-xl-4">
                                <div class="form-group">
                                    <label class="form-label" for="referral_usage_limit_count">حداکثر تعداد استفاده کاربر از
                                        کد معرف</label>
                                    <input type="number" name="referral_usage_limit_count" id="referral_usage_limit_count"
                                        class="form-control" placeholder="حداکثر تعداد استفاده کاربر از کد معرف"
                                        value="{{ $referralUsageLimitCount->value }}" required>
                                    @error('otcBuyFee')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xl-6 mt-5">
                                    <label class="switch  switch-lg">
                                        <input type="checkbox" class="switch-input" name="referral_profit_status"
                                            value="1" {{ $referralProfitStatus->value ? 'checked' : '' }} />
                                        <span class="switch-toggle-slider"></span>
                                        <span class="switch-label">وضعیت سیستم رفرال(فعال/غیرفعال)</span>
                                    </label>
                                </div>
                            </div>
                            <div class=" d-flex justify-content-start mt-5">

                                <button class="btn btn-primary ">
                                    <i class="fa fa-save mx-2"></i>
                                    ذخیره
                                </button>

                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
        <div class="col-md-6">

            <div class="card">
                <div class="card-body">
                    <div class="card-title header-elements">
                        <h5 class="m-0 me-2">تنظیمات و پیکربندی مجوزها </h5>
                    </div>
                    <form action="{{ route('admin.setting.int.update-permissions') }}" method="post">
                        @csrf
                        <button class="btn btn-success w-100">
                            <i class="fa fa-refresh mx-2"></i>
                            بروزرسانی مجوزها
                        </button>
                    </form>
                    <h6 class="text-center my-3">آخرین مجوزهای اضافه شده:</h6>
                    @foreach ($last3permissions as $last3permission)
                        <p>{{ $last3permission->name }}</p>
                    @endforeach

                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="card-title header-elements">
                        <h5 class="m-0 me-2">تنظیمات اسپات</h5>
                    </div>
                    <form action="{{ route('admin.setting.int.update-spot-settings') }}" method="post">
                        @csrf
                        <div class="row mt-5">
                            <div class="col-xl-6 mt-3">
                                <label class="switch switch-lg">
                                    <input type="checkbox" class="switch-input" name="spot_trading_enabled"
                                        value="1"
                                        {{ $spotTradingEnabled && $spotTradingEnabled->value ? 'checked' : '' }} />
                                    <span class="switch-toggle-slider"></span>
                                    <span class="switch-label">فعال‌سازی معاملات اسپات</span>
                                </label>
                                <small class="text-muted d-block mt-2">این گزینه کل سیستم معاملات اسپات را کنترل می‌کند. در صورت غیرفعال بودن، هیچ معامله یا سفارشی امکان‌پذیر نخواهد بود</small>
                            </div>
                            <div class="col-xl-6 mt-3">
                                <label class="switch switch-lg">
                                    <input type="checkbox" class="switch-input" name="spot_ticker_enabled"
                                        value="1"
                                        {{ $spotTickerEnabled && $spotTickerEnabled->value ? 'checked' : '' }} />
                                    <span class="switch-toggle-slider"></span>
                                    <span class="switch-label">فعال‌سازی Spot Ticker</span>
                                </label>
                                <small class="text-muted d-block mt-2">این گزینه نمایش قیمت‌های لحظه‌ای را کنترل
                                    می‌کند</small>
                            </div>
                            <div class="col-xl-6 mt-3">
                                <label class="switch switch-lg">
                                    <input type="checkbox" class="switch-input" name="order_matching_enabled"
                                        value="1"
                                        {{ $orderMatchingEnabled && $orderMatchingEnabled->value ? 'checked' : '' }} />
                                    <span class="switch-toggle-slider"></span>
                                    <span class="switch-label">فعال‌سازی Order Matching</span>
                                </label>
                                <small class="text-muted d-block mt-2">این گزینه سیستم تطبیق خودکار سفارشات را کنترل
                                    می‌کند</small>
                            </div>
                        </div>
                        <div class="d-flex justify-content-start mt-5">
                            <button class="btn btn-primary">
                                <i class="fa fa-save mx-2"></i>
                                ذخیره
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="card-title header-elements">
                        <h5 class="m-0 me-2">تنظیمات تجمیع و برداشت از صرافی های مرجع
                            <span
                                class="text-{{ $exchangeWithdrawalStatus->value ? 'success' : 'danger' }}">({{ $exchangeWithdrawalStatus->value ? 'فعال' : 'غیرفعال' }})</span>
                        </h5>

                    </div>
                    <form action="{{ route('admin.setting.int.update-exchange-withdrawal-setting') }}" method="post">
                        @csrf

                        <div class="col mt-5">
                            <p>مدل تجمیع و برداشت از صرافی مرجع (زمان/تعداد)</p>
                            <div class="form-check form-check-inline">
                                <input name="exchange_withdrawal_type" class="form-check-input" type="radio"
                                    id="exchange_withdrawal_period_time_checkbox"
                                    {{ $exchangeWithdrawalType->value === 'exchange_withdrawal_period_time' ? 'checked' : '' }}
                                    value="exchange_withdrawal_period_time">
                                <label class="form-check-label" for="exchange_withdrawal_period_time_checkbox">برحسب
                                    زمان</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input name="exchange_withdrawal_type" class="form-check-input" type="radio"
                                    id="exchange_withdrawal_period_buy_checkbox"
                                    {{ $exchangeWithdrawalType->value === 'exchange_withdrawal_period_buy' ? 'checked' : '' }}
                                    value="exchange_withdrawal_period_buy">
                                <label class="form-check-label" for="exchange_withdrawal_period_buy_checkbox">برحسب تعداد
                                    خرید</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input name="exchange_withdrawal_type" class="form-check-input" type="radio"
                                    id="exchange_withdrawal_both_type"
                                    {{ $exchangeWithdrawalType->value === 'exchange_withdrawal_both_type' ? 'checked' : '' }}
                                    value="exchange_withdrawal_both_type">
                                <label class="form-check-label" for="exchange_withdrawal_both_type">هردو</label>
                            </div>
                        </div>
                        <div class="row mt-5">
                            <div class="col-xl-4">
                                <div class="form-group">
                                    <label class="form-label" for="exchange_withdrawal_period_time">پارامتر زمان برای
                                        برداشت از صرافی مرجع</label>
                                    <input name="exchange_withdrawal_period_time" id="exchange_withdrawal_period_time"
                                        class="form-control" placeholder="پارامتر زمان برای برداشت از صرافی مرجع"
                                        value="{{ $exchangeWithdrawalPeriodTime->value }}" required>
                                    @error('otcBuyFee')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-xl-4">
                                <div class="form-group">
                                    <label class="form-label" for="exchange_withdrawal_period_buy">پارامتر تعداد خرید
                                        برای برداشت از صرافی مرجع</label>
                                    <input name="exchange_withdrawal_period_buy" id="exchange_withdrawal_period_buy"
                                        class="form-control" placeholder="پارامتر تعداد خرید برای برداشت از صرافی مرجع"
                                        value="{{ $exchangeWithdrawalPeriodBuy->value }}" required>
                                    @error('otcSellFee')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xl-6 mt-5">
                                    <label class="switch  switch-lg">
                                        <input type="checkbox" class="switch-input" name="exchange_withdrawal_status"
                                            value="1" {{ $exchangeWithdrawalStatus->value ? 'checked' : '' }} />
                                        <span class="switch-toggle-slider"></span>
                                        <span class="switch-label">وضعیت سیستم تجمیع (فعال/غیرفعال)</span>
                                    </label>
                                </div>
                            </div>
                            <div class=" d-flex justify-content-start mt-5">

                                <button class="btn btn-primary ">
                                    <i class="fa fa-save mx-2"></i>
                                    ذخیره
                                </button>

                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="card-title header-elements">
                        <h5 class="m-0 me-2">آدرس های (HOT Wallet)</h5>
                    </div>
                    @foreach ($exchangeWalletChains as $walletChain)
                        <form action="{{ route('admin.wallet.update-chain-address', ['wallet_chain' => $walletChain]) }}"
                            method="post">
                            @csrf
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="input-group col-5">
                                        <button type="submit" class="btn btn-outline-success">تغییر</button>
                                        <input type="text" class="form-control font-number " dir="ltr"
                                            name="address" value="{{ $walletChain->address }}">
                                        <span class="input-group-text">{{ $walletChain->wallet->currency_symbol }}
                                            ({{ $walletChain->currency_chain }})
                                        </span>
                                    </div>
                                </div>

                            </div>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="row">

    </div>

@endsection
