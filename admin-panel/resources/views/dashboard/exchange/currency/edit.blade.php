@extends('dashboard.layout.master')
@section('title', ' ویرایش '. $currencies->code)
@section('content')
    <div class="row">

        <!-- User Content -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('admin.admin.update', ['admin' => $currencies])}}" method="post">
                        @method('PATCH')
                        @csrf
                        <div class="row">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">نام</label>
                                    <input name="name"
                                           id="name"
                                           class="form-control"
                                           placeholder="نام را وارد کنید."
                                           value="{{$currencies->name}}"
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
                                           placeholder="Symbol را وارد کنید." value="{{$currencies->symbol}}" required>
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
                                           value="{{$currencies->code}}">
                                    @error('code')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mt-3">
                                    <label for="is_active">وضعیت</label>
                                    <input type="email" name="is_active" id="is_active" class="form-control"
                                           placeholder="وضعیت را وارد کنید"
                                           value="{{$currencies->is_active}}">
                                    @error('is_active')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 mt-3">
                                <div class="form-group ">
                                    <label for="network">شبکه</label>
                                    <select id="network" name="network" class="form-select text-capitalize mb-md-0 ">
                                        <option value="female">دختر</option>
                                        <option value="male"}>پسر</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3 mt-5">
                                <button class="btn btn-primary text-right">
                                    <i class="fa fa-save mx-2"></i>
                                    ثبت تغییرات
                                </button>
                            </div>

                    </form>
                </div>
            </div>
        </div>
        <!--/ User Content -->
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

