@extends('dashboard.layout.master')
@section('title', 'افزودن کاربر جدید')
@section('content')

    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-5">فرم افزودن کاربر</h5>
            <form action="{{route('admin.user.store')}}" method="post">
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
                            >
                            @error('first_name')
                            <small class="text-danger">danger</small>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="last_name">نام خانوادگی:</label>
                            <input name="last_name" id="last_name" class="form-control"
                                   placeholder="نام خانوادگی را وارد کنید."  value="{{old('last_name')}}">
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
                               value="{{old('email')}}" required>
                        @error('email')
                        <small class="text-danger">{{$message}}</small>
                        @enderror

                    </div>

                    <div class="col-md-6 mt-3">

                            <label for="mobile" class="form-label">شماره تماس</label>
                            <input name="mobile" id="mobile"
                                   @class(['form-control','is-invalid' => $errors->has('mobile')])
                                   placeholder="شماره تماس را وارد کنید."
                                   value="{{old('mobile')}}">
                            @error('mobile')
                                <small class="text-danger">{{$message}}</small>
                            @enderror

                    </div>

                    <div class="col-md-6 mt-3">

                            <label for="national_code" class="form-label">کد ملی</label>
                            <input name="national_code" id="national_code"
                                   @class(['form-control','is-invalid' => $errors->has('national_code')])
                                   placeholder="کد ملی را وارد کنید."
                                   value="{{old('national_code')}}">
                            @error('national_code')
                                <small class="text-danger">{{$message}}</small>
                            @enderror

                    </div>

                    <div class="col-md-6 mb-1">
                        <div class="form-group mt-3">
                            <label class="form-label" for="status">وضعیت کاربری:</label>
                            <select id="status" name="status" class="form-select text-capitalize mb-md-0 ">
                                <option {{ old('status') == 'active' ? 'selected' : '' }} value="active">فعال</option>
                                <option {{ old('status') == 'inactive' ? 'selected' : '' }} value="inactive" >غیرفعال</option>
                                <option {{ old('status') == 'suspended' ? 'selected' : '' }} value="suspended" >تعلیق شده</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group mt-3">
                            <label for="password">کلمه عبور</label>
                            <input type="text"
                                   name="password"
                                   id="password"
                                   class="form-control font-number"
                                   placeholder="کلمه ی عبور"
                                   value="{{old('password') ?? generateComplexPassword(12)}}">
                        </div>
                    </div>

                    <div class="col-md-6 mb-1">
                        <div class="form-group mt-3">
                            <label class="form-label" for="kyc_status">وضعیت احراز هویت(KYC):</label>
                            <select id="kyc_status" name="kyc_status" class="form-select text-capitalize mb-md-0 ">
                                <option {{ old('kyc_status') == 'approved' ? 'selected' : '' }} value="approved">مورد قبول</option>
                                <option {{ old('kyc_status') == 'pending' ? 'selected' : '' }} value="pending">در انتظار تایید</option>
                                <option {{ old('kyc_status') == 'rejected' ? 'selected' : '' }} value="rejected">رد شده</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6 mt-3">

                        <label for="introducer_code" class="form-label">کد معرف (اختیاری) :</label>
                        <input name="introducer_code" id="introducer_code"
                               @class(['font-number','form-control','is-invalid' => $errors->has('introducer_code')])
                               placeholder="کد معرف را وارد کنید."
                               value="{{old('introducer_code')}}">
                        @error('introducer')
                        <small class="text-danger">{{$message}}</small>
                        @enderror

                    </div>


                    <div class="col-md-6">
                        <div class="form-group mt-3">
                            <label for="description">توضیحات</label>
                            <textarea class="form-control" name="description" id="description"
                                      rows="3">{{old('description')}}</textarea>
                        </div>
                    </div>

                </div>
                <div class=" d-flex justify-content-center mt-3">

                        <button class="btn btn-primary w-100">
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
          ])

@endsection

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection
