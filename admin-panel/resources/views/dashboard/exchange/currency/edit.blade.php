@extends('dashboard.exchange.currency.layout.master')
@section('title', 'ویرایش کوپون')
@section('currency-body')

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <h5 class="card-header">{{ $currency->name }}</h5>
                <div class="card-body">

                    <form action="{{ route('admin.currency.update', ['currency' => $currency]) }}" method="post"
                        enctype="multipart/form-data">
                        @method('PATCH')
                        @csrf
                        <h6>1. اطلاعات کوین</h6>
                        <div class="row">
                            <div class="col-lg-4 mt-5">
                                <div class="form-group">
                                    <label class="form-label" for="name">نام</label>
                                    <input name="name" id="name" class="form-control"
                                        placeholder="نام را وارد کنید." value="{{ $currency->name }}" required>
                                    @error('name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-4 mt-5">
                                <div class="form-group">
                                    <label class="form-label" for="persian_name">نام فارسی</label>
                                    <input name="persian_name" id="persian_name" class="form-control"
                                        placeholder="نام فارسی را وارد کنید." value="{{ $currency->persian_name }}"
                                        required>
                                    @error('persian_name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-4 mt-5">
                                <div class="form-group">
                                    <label class="form-label" for="symbol">Symbol</label>
                                    <input name="symbol" id="symbol" class="form-control"
                                        placeholder="Symbol را وارد کنید." value="{{ $currency->symbol }}" required>
                                    @error('Symbol')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="w-100"></div>
                            <div class="col-lg-3 mt-5">
                                <div class="form-group">
                                    <label class="form-label" for="max_auto_withdraw_amount">حداکثر مقدار قابل برداشت بدون
                                        نیاز به تایید ادمین</label>
                                    <input name="max_auto_withdraw_amount" class="form-control font-number"
                                        placeholder="حداکثر مقدار قابل برداشت"
                                        value="{{ formatNumberTrimZeros($currency->max_auto_withdraw_amount) }}" required>
                                    @error('min_deposit_amount')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="w-100"></div>
                            <div class="col-lg-3 mt-5">
                                <div class="form-group">
                                    <label class="form-label" for="price_precision">دقت اعشار قیمت کوین</label>
                                    <input type="number" name="price_precision" id="price_precision" class="form-control"
                                        placeholder="دقت قیمت" value="{{ $currency->price_precision }}" required>
                                    @error('price_precision')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-3 mt-5">
                                <div class="form-group">
                                    <label class="form-label" for="amount_precision">دقت اعشار مقدار کوین</label>
                                    <input type="number" name="amount_precision" id="amount_precision" class="form-control"
                                        placeholder="دقت مقدار" value="{{ $currency->amount_precision }}" required>
                                    @error('amount_precision')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="w-100"></div>
                            <div class="col-md-6 mt-5">
                                <div class="form-group">
                                    <label class="form-label" for="logo">تصویر کوین:</label>
                                    <input class="form-control-file form-control" type="file" id="logo"
                                        name="logo">
                                    @error('img_filename')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="w-100"></div>
                            <div class="col-md-6 mt-5">
                                <label class="switch  switch-lg">
                                    <input type="checkbox" class="switch-input" name="is_internal_transfer_active"
                                        value="1" {{ $currency->is_internal_transfer_active ? 'checked' : '' }} />
                                    <span class="switch-toggle-slider"></span>
                                    <span class="switch-label">وضعیت انتقال داخلی کوین</span>
                                </label>
                            </div>
                            <div class="col-md-6 mt-5">
                                <label class="switch  switch-lg">
                                    <input type="checkbox" class="switch-input" name="is_active"
                                        value="1" {{ $currency->is_active ? 'checked' : '' }} />
                                    <span class="switch-toggle-slider"></span>
                                    <span class="switch-label">وضعیت کوین (فعال/غیرفعال)</span>
                                </label>
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
    </div>

@endsection
