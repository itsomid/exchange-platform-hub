@extends('dashboard.layout.master')
@section('title', 'ویرایش بازار')
@section('content')

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <h5 class="card-header d-flex justify-content-between">
                    <div>
                        بازار{{$market->baseCurrency->symbol}}/{{$market->quoteCurrency->symbol}}
                    </div>

                    <div class=" d-flex flex-column">
                        <div class="d-flex gap-2 align-items-center mb-3 flex-wrap font-number">
                            <div class="badge rounded bg-label-success">+4.2%</div>
                            <h2 class="mb-0">
                                <span class="card-subtitle h3">USDT</span>
                                {{number_format($market->price,2)}}
                            </h2>

                        </div>

                    </div>
                </h5>
                <div class="card-body">

                    <form action="{{route('admin.market.update',['market'=>$market])}}" method="post">
                        @method('PATCH')
                        @csrf
                        <h6>اطلاعات بازار</h6>
                        <div class="row">

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label" for="symbol">کوین پایه</label>
                                    <input name="symbol" id="symbol" class="form-control"
                                           value="{{$market->baseCurrency->symbol}}" required disabled>
                                    @error('Symbol')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label" for="symbol">ارز متقابل </label>
                                    <input name="symbol" id="symbol" class="form-control"
                                           value="{{$market->quoteCurrency->symbol}}" required disabled>
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
                                           value="{{$market->min_trade_amount}}"
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
                                           value="{{formatNumber($market->max_trade_amount,2)}}" required>
                                    @error('max_trade_amount')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row mt-5">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label" for="exchange_price">قیمت صرافی</label>
                                    <input name="exchange_price" id="exchange_price" class=" form-control"
                                           placeholder="قیمت صرافی"
                                           value="{{formatNumber($market->exchange_price,2)}}"
                                           required>
                                    @error('exchange_price')
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
                                           value="1" {{ $market->is_active ? 'checked' : '' }}/>
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


