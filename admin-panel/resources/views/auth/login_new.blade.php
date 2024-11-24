@extends('auth.layout.master')
@section('title','ورود به پنل ادمین')
@section('content')

    <div class="authentication-wrapper authentication-cover">
        <div class="authentication-inner py-6">
            <!-- Login -->
            <div class="card">
                <div class="card-body">
                    <!-- Logo -->
                    <div class="app-brand justify-content-center mb-6">
                        <a href="{{url('/')}}" class="app-brand-link">

                            <span class="app-brand-text demo text-heading fw-bold">بیتکس روم</span>
                        </a>
                    </div>

                    <!-- /Logo -->
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
                                name="username"
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
                        <div class="mb-6">
                            <button class="btn btn-primary d-flex w-100 my-3 " type="submit">ورود</button>
                        </div>
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
            <!-- /Register -->
        </div>
    </div>

@endsection
