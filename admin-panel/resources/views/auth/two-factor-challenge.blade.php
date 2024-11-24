@extends('auth.layout.master')
@section('content')
<div class="auth">
    <div class="auth-form">
        <!-- Logo -->
        <div class="app-brand justify-content-center mb-4 mt-2">
            <a href="index.html" class="app-brand-link justify-content-center gap-2 ">
                <img class="img-fluid w-100" src="{{asset('images/Logo.svg')}}">
            </a>
        </div>
        <!-- /Logo -->
        <h4 class="mb-3 pt-2">
            کد تایید ۲ مرحله‌ای
        </h4>
        <p>کد تایید پیامک شده به شماره همراه یا ایمیل را وارد کنید</p>
        <form class="mb-3" action="{{route('two-factor.login')}}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="code" class="form-label">کد تایید</label>
                <input
                    type="number"
                    class="form-control"
                    id="code"
                    name="code"
                    placeholder="کد تایید را وارد کنید"
                    autofocus />
            </div>
            <div class="mb-3">
                <button class="btn btn-primary d-grid w-100" type="submit">ورود به داشبورد</button>
            </div>
        </form>
    </div>
</div>
@endsection
