@extends('auth.layout.master')
@section('title', 'تایید کد دومرحله ای')
@section('content')

    <div class="authentication-wrapper authentication-cover">
        <div class="authentication-inner row m-0">
            <!-- /Left Text -->
            <div class="d-none d-lg-flex col-lg-8 p-0">
                <div class="auth-cover-bg auth-cover-bg-color d-flex justify-content-center align-items-center">
                    <img src="{{ asset('images/auth/auth-login-illustration-light.png') }}" alt="auth-login-cover"
                        class="my-5 auth-illustration">

                    <img src="{{ asset('images/auth/bg-shape-image-light.png') }}" alt="auth-login-cover" class="platform-bg">
                </div>
            </div>
            <!-- /Left Text -->

            <!-- Login -->

            <div class="d-flex col-12 col-lg-4 align-items-center authentication-bg p-sm-12 p-6">
                <div class="w-px-400 mx-auto mt-12 pt-5">
                    <div class="flex flex-col gap-1">
                        <h4 class=" mui-1ji2ofx">تأیید کد دومرحله‌ای</h4>
                        <p class="MuiTypography-root mui-14ug9cz">
                            برای ورود، لطفاً کد دومرحله‌ای را از برنامه Google Authenticator خود وارد کنید.
                        </p>
                    </div>
                    <form class="flex flex-col gap-6" autocomplete="off" action="{{ route('two-factor.login.store') }}"
                        id="twoStepsForm" method="POST">
                        @csrf
                        <div class="flex flex-col gap-2">
                            <p class="MuiTypography-root mui-14ug9cz">
                                کد ۶ رقمی خود را در کادر زیر وارد کنید:
                            </p>
                            <div class="auth-input-wrapper numeral-mask-wrapper d-flex align-items-center justify-content-between mb-3"
                                style="direction: ltr">
                                <input type="text"
                                    class="form-control auth-input height-50 text-center numeral-mask mx-25 mb-1"
                                    maxlength="1" autofocus="" inputmode="numeric" pattern="[0-9]*"
                                    autocomplete="one-time-code" enterkeyhint="done" autocorrect="off" autocapitalize="off">
                                <input type="text"
                                    class="form-control auth-input height-50 text-center numeral-mask mx-25 mb-1"
                                    maxlength="1" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code"
                                    enterkeyhint="done" autocorrect="off" autocapitalize="off">
                                <input type="text"
                                    class="form-control auth-input height-50 text-center numeral-mask mx-25 mb-1"
                                    maxlength="1" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code"
                                    enterkeyhint="done" autocorrect="off" autocapitalize="off">
                                <input type="text"
                                    class="form-control auth-input height-50 text-center numeral-mask mx-25 mb-1"
                                    maxlength="1" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code"
                                    enterkeyhint="done" autocorrect="off" autocapitalize="off">
                                <input type="text"
                                    class="form-control auth-input height-50 text-center numeral-mask mx-25 mb-1"
                                    maxlength="1" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code"
                                    enterkeyhint="done" autocorrect="off" autocapitalize="off">
                                <input type="text"
                                    class="form-control auth-input height-50 text-center numeral-mask mx-25 mb-1"
                                    maxlength="1" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code"
                                    enterkeyhint="done" autocorrect="off" autocapitalize="off">
                            </div>
                            <input type="hidden" name="code" class="code" />
                            @error('code')
                                <small class="text-danger">کد امنیتی وارد شده غلط می باشد</small>
                            @enderror
                        </div>
                        <button class="btn btn-primary w-100" tabindex="0" type="submit">بررسی کد<span
                                class="MuiTouchRipple-root mui-w0pj6f"></span>
                        </button>
                        <div class="flex justify-center items-center flex-wrap gap-2">
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
            <!-- /Login -->
        </div>
    </div>

@endsection
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/js/pages-auth-two-steps.js'])
@endsection
