@extends('dashboard.layout.master')
@section('title', 'ساخت کوپون')
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('admin.currency.store')}}" method="post">
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
                                    <label for="symbol">Symbol</label>
                                    <input name="symbol" id="symbol" class="form-control"
                                           placeholder="Symbol را وارد کنید."   value="{{old('name')}}" required>
                                    @error('Symbol')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mt-3">
                                    <label for="Code">Code</label>
                                    <input name="code" id="Code" class="form-control"
                                           placeholder="کد کوپن را وارد کنید."
                                           value="{{old('name')}}">
                                    @error('code')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mt-3">
                                    <label for="is_active">وضعیت</label>
                                    <select id="is_active" name="is_active" class="form-select text-capitalize mb-md-0">
                                        <option value="1">فعال</option>
                                        <option value="0">غیرفعال</option>
                                    </select>
                                    @error('is_active')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 mt-3">
                                <div class="form-group ">
                                    <label for="network">شبکه</label>
                                    <select id="network" name="network" class="form-select text-capitalize mb-md-0">
                                        @foreach($currencies_type as  $type)
                                            <option value="{{$type->value}}">{{$type->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 mb-1">
                                <div class="form-group mt-3">
                                    <label class="form-label" for="img_filename">تصویر کوین:</label>
                                    <input class="form-control-file form-control" type="file" id="img_filename"
                                           name="img_filename">
                                    @error('img_filename')<small class="text-danger">{{$message}}</small>@enderror
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
    <style>
        .instagram {
            background: radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%);
            -webkit-background-clip: text;
            /* Also define standard property for compatibility */
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
@endsection

