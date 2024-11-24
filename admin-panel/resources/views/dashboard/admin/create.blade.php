@extends('dashboard.layout.master')
@section('title', 'افزودن پرسنل جدید')
@section('content')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">فرم افزودن پرسنل</h5>
            <form action="{{route('admin.admin.store')}}" method="post">
                @csrf
                <div class="row">

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="first_name">نام</label>
                            <input name="first_name"
                                   id="first_name"
                                   class="form-control"
                                   placeholder="نام را وارد کنید."
                                   value="{{old('first_name')}}"
                                   required>

                            @error('first_name')
                            <small class="text-danger">{{$message}}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="last_name">نام خانوادگی</label>
                            <input name="last_name" id="last_name" class="form-control"
                                   placeholder="نام خانوادگی را وارد کنید." value="{{old('last_name')}}" required>
                            @error('last_name')
                            <small class="text-danger">{{$message}}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mt-3">
                            <label for="mobile">شماره تماس</label>
                            <input name="mobile" id="mobile" class="form-control" placeholder="شماره تماس را وارد کنید."
                                   value="{{old('mobile')}}">
                            @error('mobile')
                            <small class="text-danger">{{$message}}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mt-3">
                            <label for="mobile">ایمیل</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="ایمیل را وارد کنید"
                                   value="{{old('email')}}">
                            @error('email')
                            <small class="text-danger">{{$message}}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6 mb-1">
                        <div class="form-group mt-3">
                            <label class="" for="select-basic">نقش ها</label>
                            <select class="select2 form-select" multiple id="select-basic" name="roles[]"
                                    data-placeholder="لطفا نقش را انتخاب کنید">
                                @foreach($roles as $role)
                                    <option value="{{$role->id}}">{{$role->name}} | {{$role->persian_name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mt-3">
                            <label for="password">کلمه ی عبور</label>
                            <input type="text"
                                   name="password"
                                   id="password"
                                   class="form-control"
                                   placeholder="کلمه ی عبور"
                                   value="{{old('password') }}">
                            @error('password')
                            <small class="text-danger">{{$message}}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6 mb-1">
                        <div class="form-group mt-3">
                            <label class="form-label" for="gender">جنسیت:</label>
                            <select id="gender" name="gender" class="form-select text-capitalize mb-md-0 ">
                                <option  value="female">دختر</option>
                                <option  value="male" selected>پسر</option>
                            </select>
                        </div>
                    </div>
                    <hr class="mt-3">
                    <h5 class="card-title">شبکه های اجتماعی</h5>
                    <div class="col-md-6 mt-3">
                        <label class="form-label" for="email">ایمیل</label>
                        <div class="input-group input-group-merge">
                            <span id="basic-icon-default-fullname2" class="input-group-text">
                                <i class="fa-regular fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="ایمیل را وارد کنید."
                                   aria-label="@example.com" aria-describedby=""/>

                        </div>
                        @error('email')
                        <small class="text-danger">{{$message}}</small>
                        @enderror
                    </div>

                    <div class="col-md-6 mt-3">
                        <label class="form-label" for="instagram">آیدی اینستاگرام</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text">
                                <i class="fa-brands fa-instagram instagram"></i>
                            </span>
                            <input type="text" name="instagram" id="instagram" class="form-control "
                                   placeholder="آیدی اینستاگرام را وارد کنید. " value="{{old('instagram')}}">

                        </div>
                        @error('instagram')
                        <small class="text-danger">{{$message}}</small>
                        @enderror
                    </div>
                    <div class="col-md-6 mt-5">
                        <label for="telegram">آیدی تلگرام</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text">
                                <i class="fa-brands fa-telegram" style="color: #0088cc"></i>
                            </span>
                            <input type="text" name="telegram" id="telegram" class="form-control"
                                   placeholder="آیدی تلگرام را وارد کنید. " value="{{old('telegram')}}">

                        </div>
                        @error('telegram')
                        <small class="text-danger">{{$message}}</small>
                        @enderror
                    </div>
                    <div class="col-md-6 mt-5">
                        <label for="whatsapp">آیدی واتس اپ</label>
                        <div class="input-group input-group-merge">
                            <span class="input-group-text">
                                <i class="fa-brands fa-whatsapp" style="color: #2fb215"></i>
                            </span>
                            <input type="text" name="whatsapp" id="whatsapp" class="form-control"
                                   placeholder="آیدی واتس اپ را وارد کنید. " value="{{old('whatsapp')}}">
                        </div>
                        @error('whatsapp')
                        <small class="text-danger">{{$message}}</small>
                        @enderror
                    </div>
                </div>
                <div class=" d-flex justify-content-center mt-3">
                    <div class="col-md-1">
                        <button class="btn btn-primary w-100">
                            <i class="fa fa-save mx-2"></i>
                            ذخیره
                        </button>
                    </div>
                </div>
            </form>
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
