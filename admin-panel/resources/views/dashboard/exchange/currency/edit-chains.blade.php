@extends('dashboard.exchange.currency.layout.master')
@section('title', 'ساخت کوپون')
@section('currency-body')

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="d-flex align-items-center">
                    <h5 class="card-header">{{$currency->name}}</h5>
                    @if(!count($currency->chains))
                        <span class="badge bg-danger">بدون شبکه</span>
                    @endif
                </div>

                <div class="card-body">
                    @if(count($currency->chains))
                        <form action="{{route('admin.currency.chains.update',['currency'=>$currency])}}" method="post">
                            @method('PATCH')
                            @csrf
                            @foreach($currency->chains as $chain)
                                <h6> اطلاعات شبکه {{$chain->chain}}</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="form-label" for="chain">نام شبکه</label>
                                            <input name="chain"
                                                   id="chain"
                                                   class="form-control"
                                                   placeholder="نام را وارد کنید."
                                                   value="{{$chain->chain}}"
                                                   disabled
                                            >
                                            @error('name')
                                            <small class="text-danger">{{$message}}</small>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-5">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="form-label" for="min_deposit_amount_{{$chain->id}}">حداقل مقدار واریز</label>
                                            <input  name="chains[{{$chain->id}}][min_deposit_amount]" id="min_deposit_amount_{{$chain->id}}" class="form-control font-number"
                                                    placeholder="Symbol را وارد کنید." value="{{formatNumberTrimZeros($chain->min_deposit_amount)}}" required>
                                            @error('min_deposit_amount')
                                            <small class="text-danger">{{$message}}</small>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="form-label" for="min_withdraw_amount_{{$chain->id}}">حداقل مقدار برداشت</label>
                                            <input  name="chains[{{$chain->id}}][min_withdraw_amount]" id="min_withdraw_amount_{{$chain->id}}" class="form-control font-number"
                                                    placeholder="حداقل مقدار را وارد کنید." value="{{formatNumberTrimZeros($chain->min_withdraw_amount)}}" required>
                                            @error('min_withdraw_amount')
                                            <small class="text-danger">{{$message}}</small>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-5">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="form-label" for="deposit_delay_minutes_{{$chain->id}}">تاخیر در واریز به دقیقه</label>
                                            <input  name="chains[{{$chain->id}}][deposit_delay_minutes]" id="deposit_delay_minutes_{{$chain->id}}" class="form-control"
                                                    placeholder="زمان را وارد کنید." value="{{$chain->deposit_delay_minutes}}" required>
                                            <div id="defaultFormControlHelp" class="form-text">بعد از اینکه یک واریز شناسایی شد، سیستم برای انجام برخی اقدامات امنیتی یا بررسی‌های اضافی (مانند تأیید تعداد تاییدیه‌های بلاک‌چین) یک تاخیر زمانی را اعمال می‌کند.</div>
                                            @error('deposit_delay_minutes')
                                            <small class="text-danger">{{$message}}</small>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="form-label" for="safe_confirmations_{{$chain->id}}">حداقل تعداد تایید شبکه برای واریز</label>
                                            <input  name="chains[{{$chain->id}}][safe_confirmations]" id="safe_confirmations_{{$chain->id}}" class="form-control"
                                                    placeholder="زمان را وارد کنید." value="{{$chain->safe_confirmations}}" required>
                                            @error('safe_confirmations')
                                            <small class="text-danger">{{$message}}</small>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-5">
                                    <div class="col-md-3 ">
                                        <div class="form-group">
                                            <label class="form-label" for="exchange_withdrawal_fee_{{$chain->id}}">فی صرافی برای برداشت (واحد)</label>
                                            <input  type="number" name="chains[{{$chain->id}}][exchange_withdrawal_fee]" id="exchange_withdrawal_fee_{{$chain->id}}" class="form-control font-number"
                                                    placeholder="فی صرافیی را وارد کنید." value="{{formatNumber($chain->exchange_withdrawal_fee,$currency->precision)}}" required>
                                            @error('exchange_withdrawal_fee')
                                            <small class="text-danger">{{$message}}</small>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="form-label" for="network_fee_{{$chain->id}}" >فی شبکه برای برداشت (واحد)</label>
                                            <input  name="chains[{{$chain->id}}][network_fee]" id="network_fee_{{$chain->id}}" class="form-control font-number"
                                                    placeholder="فی شبکه." value="{{$chain->network_fee}}" disabled required>
                                            @error('network_fee')
                                            <small class="text-danger">{{$message}}</small>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="w-100"></div>
                                    <div class="col-md-6 mt-5">
                                        <label class="switch  switch-lg">
                                            <input type="checkbox" class="switch-input" name="chains[{{$chain->id}}][deposit_enabled]" value="1" {{ $chain->deposit_enabled ? 'checked' : '' }} />
                                            <span class="switch-toggle-slider"></span>
                                            <span class="switch-label">وضعیت واریز</span>
                                        </label>
                                    </div>
                                    <div class="w-100"></div>
                                    <div class="col-md-6 mt-5">
                                        <label class="switch  switch-lg">
                                            <input type="checkbox" class="switch-input" name="chains[{{$chain->id}}][withdraw_enabled]" value="1" {{ $chain->withdraw_enabled ? 'checked' : '' }} />
                                            <span class="switch-toggle-slider"></span>
                                            <span class="switch-label">وضعیت برداشت</span>
                                        </label>
                                    </div>
                                </div>
                                <hr class="my-6 mx-n4">
                            @endforeach
                            <div class=" d-flex justify-content-start mt-5">

                                    <button class="btn btn-primary ">
                                        <i class="fa fa-save mx-2"></i>
                                        ذخیره
                                    </button>

                            </div>

                        </form>
                    @else
                        <div class="alert alert-danger" role="alert">
                            ساخت شبکه برای تخصیص کوین به بازار الزامی است.
                        </div>
                    @endempty

                </div>
            </div>
        </div>
    </div>

@endsection


