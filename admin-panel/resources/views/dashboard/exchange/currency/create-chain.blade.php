@extends('dashboard.exchange.currency.layout.master')
@section('title', 'ساخت کوپون')
@section('currency-body')

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <h5 class="card-header">ایجاد شبکه جدید برای {{$currency->name}} </h5>
                <div class="card-body">
                    <form action="{{route('admin.currency.chains.store',['currency'=>$currency])}}" method="post">
                        @csrf
                        <h6></h6>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label" for="chain">انتخاب شبکه :</label>
                                    <select id="chain" name="chain" class="form-select text-capitalize mb-md-0 ">
                                        @foreach(\App\Enums\CurrencyChainEnum::cases() as $chain)
                                            <option
                                                value="{{$chain->value}}" >{{$chain->value}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-5">
                            <div class="col-md-3 ">
                                <div class="form-group">
                                    <label class="form-label" for="min_deposit_amount">حداقل مقدار
                                        واریز</label>
                                    <input name="min_deposit_amount"
                                           id="min_deposit_amount}" class="form-control"
                                           placeholder="حداقل مقدار واریز را وارد کنید." value="{{old('min_deposit_amount')}}"
                                           required>
                                    @error('min_deposit_amount')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label" for="min_withdraw_amount">حداقل مقدار
                                        برداشت</label>
                                    <input name="min_withdraw_amount"
                                           id="min_withdraw_amount" class="form-control"
                                           placeholder="حداقل مقدار برداشت را وارد کنید."
                                           value="{{old('min_withdraw_amount')}}" required>
                                    @error('min_withdraw_amount')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row mt-5">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label" for="deposit_delay_minutes">تاخیر در واریز
                                        به دقیقه</label>
                                    <input name="deposit_delay_minutes"
                                           id="deposit_delay_minutes" class="form-control"
                                           placeholder="زمان را وارد کنید." value="{{old('deposit_delay_minutes')}}"
                                           required>
                                    <div id="defaultFormControlHelp" class="form-text">بعد از اینکه یک واریز شناسایی شد،
                                        سیستم برای انجام برخی اقدامات امنیتی یا بررسی‌های اضافی (مانند تأیید تعداد
                                        تاییدیه‌های بلاک‌چین) یک تاخیر زمانی را اعمال می‌کند.
                                    </div>
                                    @error('deposit_delay_minutes')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label" for="safe_confirmations">حدافل تعداد تایید
                                        شبکه برای واریز</label>
                                    <input name="safe_confirmations"
                                           id="safe_confirmations" class="form-control"
                                           placeholder="زمان را وارد کنید." value="{{old('safe_confirmations')}}"
                                           required>
                                    @error('safe_confirmations')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mt-5">
                            <div class="col-md-3 ">
                                <div class="form-group">
                                    <label class="form-label" for="exchange_withdrawal_fee">فی صرافی برای برداشت
                                        (واحد)</label>
                                    <input name="exchange_withdrawal_fee" id="exchange_withdrawal_fee"
                                           class="form-control"
                                           placeholder="فی صرافیی را وارد کنید." value="{{old('exchange_withdrawal_fee')}}"
                                           required>
                                    @error('exchange_withdrawal_fee')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label" for="network_fee">فی شبکه برای برداشت
                                        (واحد)</label>
                                    <input name="network_fee" id="network_fee"
                                           class="form-control"
                                           placeholder="فی شبکه." value="{{old('network_fee')}}" required>
                                    @error('network_fee')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="w-100"></div>
                            <div class="col-md-6 mt-5">
                                <label class="switch  switch-lg">
                                    <input type="checkbox" class="switch-input"
                                           name="deposit_enabled"
                                           value="1"  />
                                    <span class="switch-toggle-slider"></span>
                                    <span class="switch-label">وضعیت واریز</span>
                                </label>
                            </div>
                            <div class="w-100"></div>
                            <div class="col-md-6 mt-5">
                                <label class="switch  switch-lg">
                                    <input type="checkbox" class="switch-input"
                                           name="withdraw_enabled"
                                           value="1"  />
                                    <span class="switch-toggle-slider"></span>
                                    <span class="switch-label">وضعیت برداشت</span>
                                </label>
                            </div>
                        </div>
                        <hr class="my-6 mx-n4">

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


