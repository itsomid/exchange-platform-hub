@extends('mail.layout.master-mail-layout')
@section('title', 'فعال‌سازی حساب کاربری')
@section('header')
    فعال‌سازی حساب کاربری
@endsection
@section('content')
    <div style="text-align: right;">
        <p>سلام {{ $user->name }} عزیز،</p>
        <p>از ثبت‌نام شما در <strong>بیتکس روم</strong> سپاسگزاریم! برای فعال‌سازی حساب خود، لطفاً از کد فعال‌سازی زیر استفاده کنید:</p>
        <div class="activation-code">{{ $token }}</div>
        <p>همچنین می‌توانید با کلیک روی دکمه زیر حساب خود را فعال کنید:</p>
        <div class="activation-link">
            <a href="{{ $url }}">فعال‌سازی حساب</a>
        </div>
        <p>توجه داشته باشید که کد و لینک فعال‌سازی تا <strong>{{ $expirationDate }}</strong> معتبر است.</p>
        <p>اگر شما این درخواست را ثبت نکرده‌اید، لطفاً این ایمیل را نادیده بگیرید.</p>
        <p>ما از حضور شما در کنار خود خوشحالیم!</p>
    </div>

@endsection
