@extends('dashboard.layout.master')
@section('title', 'ویرایش بازار')
@section('content')

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <h5 class="card-header">
                    ساخت بازار جدید
                </h5>
                <div class="card-body">

                    <form action="{{route('admin.market.store')}}" method="post">
                        @csrf
                        <h6>اطلاعات بازار</h6>
                        <div class="row">

                            <div class="col-md-3">

                                <div class="form-group">
                                    <label class="form-label" for="symbol">کوین پایه:</label>
                                    <select id="symbol" class="form-select" name="symbol"
                                            data-placeholder="لطفا کوین پایه بازار را انتخاب کنید.">
                                        @foreach($currencies as $currency)
                                            <option value="{{$currency->id}}">
                                                <x title='' class='tagify__tag__removeBtn' role='button'
                                                   aria-label='remove tag'></x>
                                                <span class='tagify__tag-text'>{{$currency->name}}</span>
                                                <div>
                                                    <div class='tagify__tag__avatar-wrap'>
                                                        <img src="{{$currency->coinLogo()}}">
                                                    </div>

                                                </div>

                                            </option>
                                        @endforeach
                                    </select>
                                    @error('currency')<small class="text-danger">{{$message}}</small>@enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label" for="symbol">ارز متقابل </label>
                                    <input name="symbol" id="symbol" class="form-control"
                                           value="USDT" required disabled>
                                    @error('Symbol')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row mt-5">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label" for="min_trade_amount">حداقل مقدار معامله در این
                                        بازار</label>
                                    <input name="min_trade_amount" id="min_trade_amount" class="form-control"
                                           placeholder="حداقل مقدار معامله در این بازار."
                                           value="{{old('min_trade_amount')}}"
                                           required>
                                    @error('min_trade_amount')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label" for="max_trade_amount">حداکثر مقدار معامله در این
                                        بازار</label>
                                    <input name="max_trade_amount" id="max_trade_amount" class="form-control"
                                           placeholder="حداکثر مقدار معامله در این بازار."
                                           value="{{formatNumber(old('min_trade_amount'),2)}}" required>
                                    @error('max_trade_amount')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row mt-5">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label" for="exchange_profit_sell">سود صرافی</label>
                                    <input name="exchange_profit_sell" id="exchange_profit_sell" class=" form-control"
                                           placeholder="سود صرافی"
                                           value="{{formatNumber(old('exchange_profit_sell'),2)}}"
                                           required>
                                    @error('exchange_profit_sell')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label" for="exchange_profit_buy">سود صرافی</label>
                                    <input name="exchange_profit_buy" id="exchange_profit_buy" class=" form-control"
                                           placeholder="سود صرافی"
                                           value="{{formatNumber(old('exchange_profit_buy'),2)}}"
                                           required>
                                    @error('exchange_profit_buy')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label" for="exchange_profit_sell">صرافی مرجع</label>
                                    <select name="exchange_id" id="" class="form-control">
                                        @foreach($exchanges as $exchange)
                                            <option @if(old('exchange_id') === $exchange->id) selected @endif value="{{ $exchange->id }}">{{ $exchange->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('exchange_id')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mt-5">
                                <label class="switch  switch-lg">
                                    <input type="checkbox" class="switch-input"
                                           name="is_active"
                                           value="1"/>
                                    <span class="switch-toggle-slider"></span>
                                    <span class="switch-label">وضعیت بازار(فعال/غیرفعال)</span>
                                </label>
                            </div>
                        </div>
                        <div class=" d-flex justify-content-start mt-5">
                            <div class="col-md-1">
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
    </div>
    </div>

@endsection
@section('vendor-script')
    @vite([
    'resources/assets/vendor/libs/select2/select2.js',
])
@endsection
@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/select2/select2.scss',
    ])
@endsection
