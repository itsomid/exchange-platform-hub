@extends('mail.layout.master-mail-layout')
@section('title', 'درخواست غیرفعال کردن ورود دو مرحله‌ای')
@section('header')
    درخواست غیرفعال کردن ورود دو مرحله‌ای
@endsection
@section('content')
    <div style="text-align: right;">
        <p>سلام عزیز،</p>
        <p>ما درخواست شما برای غیرفعال کردن ورود دو مرحله‌ای دریافت کردیم.</p>
        <p>برای غیرفعال کردن ورود دو مرحله‌ای، لطفاً روی لینک زیر کلیک کنید:</p>
        <div style="text-align: center; margin: 20px 0;">
            <a href="{{ $link }}" style="background-color: #3498db; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                غیرفعال کردن ورود دو مرحله‌ای
            </a>
        </div>
        <p>اگر شما این درخواست را ارسال نکرده‌اید، لطفاً این ایمیل را نادیده بگیرید یا با پشتیبانی تماس بگیرید.</p>
    </div>
@endsection
