@extends('dashboard.layout.master')
@section('title', 'ساخت کوپون')
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-warning" role="alert">
                       بعد از ایجاد کوین نسبت به ساخت شبکه آن اقدام کنید
                    </div>
                    <form action="{{route('admin.currency.store')}}" method="post"  enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">نام</label>
                                    <input name="name"
                                           id="name"
                                           class="form-control"
                                           placeholder="نام را وارد کنید."
                                           value="{{old('name')}}"
                                           required>
                                    @error('name')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="persian_name">نام فارسی</label>
                                    <input name="persian_name"
                                           id="persian_name"
                                           class="form-control"
                                           placeholder="نام فارسی را وارد کنید."
                                           value="{{old('persian_name')}}"
                                           required>
                                    @error('persian_name')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row mt-6">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="symbol">Symbol</label>
                                    <input name="symbol" id="symbol" class="form-control"
                                           placeholder="Symbol را وارد کنید."   value="{{old('symbol')}}" required>
                                    @error('symbol')
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
                        </div>
                            <div class="row">
                                <div class="col-md-6 mt-5">
                                    <label class="switch  switch-lg">
                                        <input type="checkbox" class="switch-input"
                                               name="is_active"
                                               value="1"/>
                                        <span class="switch-toggle-slider"></span>
                                        <span class="switch-label">وضعیت ارز(فعال/غیرفعال)</span>
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
    </div>

@endsection
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
    @vite(['resources/assets/vendor/js/forms-selects.js'])
@endsection
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

