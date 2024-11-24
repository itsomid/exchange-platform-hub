@extends('dashboard.user.layout.master')
@section('title','ویرایش رمز عبور')
@section('user-body')

    <div class="card">

        <form action="{{route('admin.user.password.edit', ['user' => $user->id])}}" method="post">
            @csrf
            @method('PATCH')
            <div class="row card-body">
                <div class="my-3 form-password-toggle">
                    <label class="form-label" for="password" @class(['border-danger'=>$errors->has('password')])>
                        کلمه عبور
                    </label>
                    <div class="input-group input-group-merge">
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-control"
                               placeholder="کلمه عبور جدید خود را وارد کنید"
                               aria-describedby="password"/>
                        <span class="input-group-text cursor-pointer">
                                <i class="fa-light fa-eye-slash"></i>
                            </span>
                    </div>
                </div>
                <div class="my-3 form-password-toggle">
                    <label class="form-label"
                           for="password_confirmation" @class(['border-danger'=>$errors->has('password')])>
                        تکرار کلمه عبور
                    </label>
                    <div class="input-group input-group-merge">

                        <input type="password"
                               id="password_confirmation"
                               name="password_confirmation"
                               class="form-control"
                               placeholder="***********"
                               aria-describedby="password_confirmation"/>
                        <span class="input-group-text cursor-pointer">
                                <i class="fa-light fa-eye-slash"></i>
                            </span>
                    </div>
                </div>
                <div class="col-12 mt-5">
                    <h6>شرایط رمز عبور:</h6>
                    <ul class="ps-3 mb-0">
                        <li class="mb-1">کلمه عبور باید حداقل از ۸ کاراکتر تشکیل شده باشد</li>
                        <li>رمز عبور میبایست ترکیبی از حروف، اعداد و کاراکترهای خاص باشد.</li>
                    </ul>
                </div>

                <div class="col-12 text-right  mt-4">

                    <button class="btn btn-primary">
                        <i class="fa fa-save mx-2"></i>
                        ثبت تغییرات
                    </button>
                </div>
            </div>
        </form>

    </div>

@endsection
