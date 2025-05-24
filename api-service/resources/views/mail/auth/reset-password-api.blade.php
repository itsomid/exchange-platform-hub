@extends('mail.layout.master-mail-layout')
@section('title', 'بازیابی رمز عبور')
@section('header')
    بازیابی رمز عبور
@endsection
@section('content')
    <div style="text-align: right;">
        <p>سلام کاربر عزیز،</p>
        <p>شما درخواست بازیابی رمز عبور داده‌اید. برای تنظیم مجدد رمز عبور خود، لطفاً روی دکمه زیر کلیک کنید:</p>
        <div class="activation-link">
            <a href="{{ $url }}" target="_blank">بازیابی رمز عبور</a>
        </div>
        <p>این لینک تا <strong>{{ $expiration }}</strong> معتبر است. اگر شما این درخواست را نداده‌اید، لطفاً این ایمیل
            را نادیده بگیرید.</p>

    </div>
@endsection

