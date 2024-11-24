@extends('dashboard.profile.layout.master')
@section('profile-body')

    <div class="card">
        <h5 class="card-header">اطلاعات کاربری</h5>
        <div class="card-body">
            <form action="{{route('admin.profile.update' ,['admin'=>$admin])}}" method="post">
                @csrf
                @method('PATCH')
                <div class="row">
                    <div class="col-6 mt-3">
                        <div class="form-group ">
                            <label for="first_name">نام</label>
                            <input
                                id="first_name"
                                name="first_name"
                                type="text"
                                class="form-control"
                                value="{{old('first_name') ?? $admin->first_name}}"
                                placeholder="نام خود را وارد کنید">
                            @error('first_name')
                            <small class="text-danger">{{$message}}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="col-6 mt-3">
                        <div class="form-group">
                            <label for="last_name">نام خانوادگی</label>
                            <input
                                id="last_name"
                                name="last_name"
                                type="text"
                                class="form-control"
                                value="{{old('last_name') ?? $admin->last_name}}"
                                placeholder="نام خانوادگی خود را وارد کنید">
                            @error('last_name')
                            <small class="text-danger">{{$message}}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="col-6 mt-3">
                        <div class="form-group ">
                            <label for="mobile">موبایل</label>
                            <input
                                id="mobile"
                                name="mobile"
                                type="text"
                                class="form-control"
                                value="{{old('mobile') ?? $admin->mobile}}"
                                placeholder="نام خود را وارد کنید">
                            @error('mobile')
                            <small class="text-danger">{{$message}}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6 mt-3">
                        <div class="form-group ">
                            <label for="gender">جنسیت:</label>
                            <select id="gender" name="gender" class="form-select text-capitalize mb-md-0 ">
                                <option value="female" {{$admin->gender === 'female'?'selected':''}}>دختر
                                </option>
                                <option value="male" {{$admin->gender === 'male'?'selected':''}}>پسر</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5 class="card-title">شبکه های اجتماعی</h5>
                    <div class="col-md-6 mt-3">
                        <label class="form-label" for="email">ایمیل</label>
                        <div class="input-group input-group-merge">
                                    <span id="basic-icon-default-fullname2" class="input-group-text">
                                        <i class="fa-regular fa-envelope"></i>
                                    </span>
                            <input type="email" class="form-control" id="email"
                                   placeholder="ایمیل را وارد کنید."
                                   value="{{$admin->email}}"
                                   name="email"
                                   aria-label="@example.com" aria-describedby=""/>
                        </div>
                        @error('email')
                        <small class="text-danger">{{$message}}</small>
                        @enderror
                    </div>

                    <div class="col-md-6 mt-3">
                        <label class="form-label" for="instagram">ایمیل</label>
                        <div class="input-group input-group-merge">
                                    <span class="input-group-text">
                                        <i class="fa-brands fa-instagram instagram"></i>
                                    </span>
                            <input type="text" name="instagram" id="instagram" class="form-control "
                                   value="{{$admin->instagram}}"
                                   placeholder="آیدی اینستاگرام را وارد کنید. ">

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
                                   placeholder="آیدی تلگرام را وارد کنید. " value="{{$admin->telegram}}">
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
                                   placeholder="آیدی واتس اپ را وارد کنید. " value="{{$admin->whatsapp}}">
                        </div>
                        @error('whatsapp')
                        <small class="text-danger">{{$message}}</small>
                        @enderror
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-primary text-right">
                            <i class="fa fa-save mx-2"></i>
                            ثبت تغییرات
                        </button>
                    </div>
                </div>


            </form>
        </div>
    </div>

@endsection

@section('vendor-style')
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


