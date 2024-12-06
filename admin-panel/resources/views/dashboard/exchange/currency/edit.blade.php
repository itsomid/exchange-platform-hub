@extends('dashboard.exchange.currency.layout.master')
@section('title', 'ویرایش کوپون')
@section('currency-body')

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <h5 class="card-header">{{$currency->name}}</h5>
                <div class="card-body">

                    <form action="{{route('admin.currency.update',['currency'=>$currency])}}" method="post"
                          enctype="multipart/form-data">
                        @method('PATCH')
                        @csrf
                        <h6>1. اطلاعات کوین</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">نام</label>
                                    <input name="name"
                                           id="name"
                                           class="form-control"
                                           placeholder="نام را وارد کنید."
                                           value="{{$currency->name}}"
                                           required>
                                    @error('name')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="symbol">Symbol</label>
                                    <input name="symbol" id="symbol" class="form-control"
                                           placeholder="Symbol را وارد کنید." value="{{$currency->symbol}}" required>
                                    @error('Symbol')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>


                            <div class="col-md-6 mb-1">
                                <div class="form-group mt-3">
                                    <label class="form-label" for="logo">تصویر کوین:</label>
                                    <input class="form-control-file form-control" type="file" id="logo"
                                           name="logo">
                                    @error('img_filename')<small class="text-danger">{{$message}}</small>@enderror
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

                            <div class=" d-flex justify-content-start mt-5">
                                <div class="col-md-1">
                                    <button class="btn btn-primary ">
                                        <i class="fa fa-save mx-2"></i>
                                        ذخیره
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection


