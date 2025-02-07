@extends('dashboard.user.layout.master')
@section('title', 'ویرایش کاربر ')
@section('user-body')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">فرم ویرایش کاربر</h5>
            <form action="{{route('admin.user.update',['user' => $user->id])}}" method="post">
                @csrf
                @method('PATCH')
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="first_name">نام</label>
                            <input name="first_name"
                                   id="first_name"
                                   class="form-control"
                                   placeholder="نام را وارد کنید."
                                   value="{{$user->first_name}}"
                            >
                            @error('name')
                            <small class="text-danger">{{$message}}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="last_name">نام خانوادگی:</label>
                            <input name="last_name" id="last_name" class="form-control"
                                   placeholder="نام خانوادگی را وارد کنید." value="{{$user->last_name}}">
                            @error('last_name')
                            <small class="text-danger">{{$message}}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6 mt-3">

                        <label for="email" class="form-label">ایمیل:</label>
                        <input name="email" id="email"
                               @class(['form-control','is-invalid' => $errors->has('email')])
                               placeholder="ایمیل کاربر را وارد کنید."
                               value="{{$user->email}}" disabled>
                        @error('email')
                        <small class="text-danger">{{$message}}</small>
                        @enderror

                    </div>
                    <div class="col-md-6 mt-3">

                        <label for="username" class="form-label">نام کاربری:</label>
                        <input name="username" id="username"
                               @class(['form-control','is-invalid' => $errors->has('username')])
                               placeholder="ایمیل کاربر را وارد کنید."
                               value="{{$user->username}}" disabled>
                        @error('email')
                        <small class="text-danger">{{$message}}</small>
                        @enderror

                    </div>
                    <div class="col-md-6">
                        <div class="form-group mt-3">
                            <label for="mobile">شماره تماس</label>
                            <input name="mobile"
                                   id="mobile"
                                   class="form-control"
                                   placeholder="شماره تماس را وارد کنید."
                                   value="{{$user->mobile}}"

                            >
                            @error('mobile')
                            <small class="text-danger">{{$message}}</small>
                            @enderror
                        </div>
                    </div>


                    <div class="col-md-6 mb-1">
                        <div class="form-group mt-3">
                            <label class="form-label" for="status">وضعیت کاربری:</label>
                            <select id="status" name="status" class="form-select text-capitalize mb-md-0 ">
                                <option {{ $user->status === \App\Enums\UserStatusEnum::ACTIVE ? 'selected' : '' }} value="active">فعال</option>
                                <option {{ $user->status === \App\Enums\UserStatusEnum::INACTIVE ? 'selected' : '' }} value="inactive">غیرفعال
                                </option>
                                <option {{ $user->status === \App\Enums\UserStatusEnum::SUSPEND ? 'selected' : '' }} value="suspended">تعلیق
                                    شده
                                </option>
                            </select>
                        </div>
                    </div>


                    <div class="col-md-6 mb-1">
                        <div class="form-group mt-3">
                            <label class="form-label" for="kyc_status">وضعیت احراز هویت (KYC):</label>
                            <select id="kyc_status" name="kyc_status" class="form-select text-capitalize mb-md-0 ">
                                <option {{ $user->kyc_status == 'approved' ? 'selected' : '' }} value="approved">مورد
                                    قبول
                                </option>
                                <option {{ $user->kyc_status == 'pending' ? 'selected' : '' }} value="pending">در انتظار
                                    تایید
                                </option>
                                <option {{ $user->kyc_status == 'rejected' ? 'selected' : '' }} value="rejected">رد
                                    شده
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6 mt-3">

                        <label for="introducer_code" class="form-label">کد معرف (اختیاری) :</label>
                        <input name="introducer_code" id="introducer_code"
                               @class(['font-number','form-control','is-invalid' => $errors->has('introducer_code')])
                               placeholder="کد معرف را وارد کنید."
                               value="{{$user->introducerReferral?->code}}">
                        @if($user->introducerReferral)
                            <p class="text-info mt-3">معرف: {{$user->introducerReferral?->user->username}}</p>
                        @endif
                        @error('introducer_code')
                        <small class="text-danger">{{$message}}</small>
                        @enderror
                    </div>

                    <div class="col-md-6 mt-4">
                        <div class="form-group">
                            <label for="description">توضیحات</label>
                            <textarea class="form-control" name="description" id="description"
                                      rows="3">{{$user->description}}</textarea>
                        </div>
                    </div>


                </div>
                <div class=" d-flex justify-content-center mt-3">

                        <button class="btn btn-primary">
                            <i class="fa fa-save mx-2"></i>
                            ذخیره
                        </button>

                </div>
            </form>
        </div>
    </div>
@endsection


@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js',
            'resources/assets/vendor/js/forms-selects.js',
'resources/assets/js/pages-auth-two-steps.js'
          ])

@endsection

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection
