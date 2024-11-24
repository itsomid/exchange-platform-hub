@extends('dashboard.layout.master')
@section('title', ' ویرایش '. $admin->fullname())
@section('content')
    <div class="row">

        <!-- User Sidebar -->
        <div class="col-xl-4 col-lg-5 col-md-5 order-1 order-md-0">
            <!-- User Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="user-avatar-section">
                        <div class="d-flex align-items-center flex-column">
                            <img class="img-fluid rounded mb-3 pt-1 mt-4"
                                 src="{{$admin->avatar()}}"
                                 height="100"
                                 width="100"
                                 alt="User avatar"/>
                            <div class="user-info text-center">
                                <h4 class="mb-2">{{$admin->fullname()}}</h4>
                            </div>
                        </div>
                    </div>
                    <p class="mt-4 small text-uppercase text-muted">جزئیات</p>
                    <div class="info-container">
                        <ul class="list-unstyled">
                            <li class="mb-2 d-flex justify-content-between">
                                <span class="fw-semibold me-1">ایمیل:</span>
                                <span>{{$admin->email}}</span>
                            </li>
                            <li class="mb-2 pt-1  d-flex justify-content-between">
                                <span class="fw-semibold me-1">شماره تماس:</span>
                                <span>{{$admin->mobile}}</span>
                            </li>
                            <li class="mb-2 pt-1  d-flex justify-content-between">
                                <span class="fw-semibold me-1">وضعیت:</span>
                                <span class="badge bg-label-{{$admin->status()?'success':'danger'}} me-1">
                                        {{$admin->status()?'فعال':'غیرفعال'}}
                                    </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- /User Card -->
        </div>
        <!--/ User Sidebar -->

        <!-- User Content -->
        <div class="col-xl-8 col-lg-7 col-md-7 order-0 order-md-1">
            <!-- User Pills -->
            <ul class="nav nav-pills flex-column flex-md-row mb-4">
                <li class="nav-item">
                    <a class="nav-link active"
                       href="{{route('admin.profile.edit')}}">
                        <i class="ti ti-user-check ti-xs me-1"></i>
                        اطلاعات کاربری
                    </a>
                </li>
            </ul>
            <!--/ User Pills -->
            <div class="card">
                <div class="card-body">
                    <form action="{{route('admin.admin.update', ['admin' => $admin])}}" method="post">
                        @method('PATCH')
                        @csrf
                        <div class="row">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="first_name">نام</label>
                                    <input name="first_name"
                                           id="first_name"
                                           class="form-control"
                                           placeholder="نام را وارد کنید."
                                           value="{{$admin->first_name}}"
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
                                           placeholder="نام خانوادگی را وارد کنید." value="{{$admin->last_name}}" required>
                                    @error('last_name')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mt-3">
                                    <label for="mobile">شماره تماس</label>
                                    <input name="mobile" id="mobile" class="form-control" placeholder="شماره تماس را وارد کنید."
                                           value="{{$admin->mobile}}">
                                    @error('mobile')
                                    <small class="text-danger">{{$message}}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mt-3">
                                    <label for="mobile">ایمیل</label>
                                    <input type="email" name="email" id="email" class="form-control" placeholder="ایمیل را وارد کنید"
                                           value="{{$admin->email}}">
                                    @error('email')
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


                            <hr class="mt-5">
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

