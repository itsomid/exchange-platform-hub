@extends('auth.layout.master')
@section('title','ورود به پنل ادمین')
@section('content')

    <div class="authentication-wrapper authentication-cover">
        <div class="authentication-inner row m-0">
            <!-- /Left Text -->
            <div class="d-none d-lg-flex col-lg-8 p-0">
                <div class="auth-cover-bg auth-cover-bg-color d-flex justify-content-center align-items-center">
                    <img src="{{ asset('images/auth/auth-login-illustration-light.png') }}" alt="auth-login-cover" class="my-5 auth-illustration" >
{{--                    <img src="{{ asset('assets/img/illustrations/auth-login-illustration-'.$configData['style'].'.png') }}" alt="auth-login-cover" class="my-5 auth-illustration" data-app-light-img="illustrations/auth-login-illustration-light.png" data-app-dark-img="illustrations/auth-login-illustration-dark.png">--}}

                    <img src="{{ asset('images/auth/bg-shape-image-light.png') }}" alt="auth-login-cover" class="platform-bg">
{{--                    <img src="{{ asset('assets/img/illustrations/bg-shape-image-'.$configData['style'].'.png') }}" alt="auth-login-cover" class="platform-bg" data-app-light-img="illustrations/bg-shape-image-light.png" data-app-dark-img="illustrations/bg-shape-image-dark.png">--}}
                </div>
            </div>
            <!-- /Left Text -->

            <!-- Login -->
            <div class="d-flex col-12 col-lg-4 align-items-center authentication-bg p-sm-12 p-6">
                <div class="w-px-400 mx-auto mt-12 pt-5">
                    <h4 class="mb-1">به بیتکس روم خوش اومدی 👋</h4>
                    <p class="mb-6">برای ورود به پنل نام کاربری و رمز عبور خود را وارد کنید.</p>
                    @if (session('status'))
                        <div class="alert alert-danger text-12" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                    <form id="formAuthentication" class="mb-4" action="{{route('login')}}" method="POST">
                        @csrf
                        <div class="mb-6">
                            <label for="username" class="form-label">شناسه کاربری</label>
                            <input
                                type="text"
                                class="form-control"
                                id="username"
                                name="email"
                                placeholder="شناسه کاربری"
                                autocomplete="username"
                                value="{{ $defaultUsername}}"
                                required
                                autofocus/>
                        </div>
                        <div class="mb-6 form-password-toggle">
                            <label class="form-label"
                                   for="password" @class(['border-danger'=>$errors->has('password')])>
                                رمز عبور
                            </label>
                            <div class="input-group input-group-merge">

                                <input type="password"
                                       id="password"
                                       name="password"
                                       class="form-control"
                                       value="{{ $defaultPassword}}"
                                       placeholder="***********"
                                       aria-describedby="password"/>
                                <span class="input-group-text cursor-pointer">
                                             <i class="fa-light fa-eye-slash"></i>
                                        </span>
                            </div>
                        </div>
                        <div class="my-8">
                            <div class="d-flex justify-content-between">
                                <div class="form-check mb-0 ms-2">
                                    <input class="form-check-input" type="checkbox" id="remember-me">
                                    <label class="form-check-label" for="remember-me">
                                        من رو به یاد داشته باش
                                    </label>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-primary d-flex w-100 my-3 " type="submit">ورود</button>
                    </form>

                    <div class="divider my-6">
                        <div class="divider-text">or</div>
                    </div>

                    <div class="d-flex justify-content-center">
                        <a href="javascript:;" class="btn btn-icon btn-label-instagram me-3">
                            <i class=" fa-brands fa-instagram-square fs-5"></i>
                        </a>

                        <a href="javascript:;" class="btn btn-icon btn-label-twitter">
                            <i class="tf-icons fa-brands fa-twitter fs-5"></i>
                        </a>
                    </div>
                </div>
            </div>
            <!-- /Login -->
        </div>
    </div>

@endsection
