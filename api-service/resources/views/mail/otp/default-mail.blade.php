@extends('mail.layout.master-mail-layout')
@section('title', 'سفارش خرید شما تکمیل شد')
@section('header')
    کد دو عاملی
@endsection
@section('content')
    <div style="text-align: right"> سلام کاربر عزیز،</div>
    <br>

    <div style="text-align: center">
        <p>کد تأیید شما:</p>
        <div class="otp-code">{{ $code }}</div>
        <p>این کد تا <strong>{{ $expiration }}</strong> معتبر است.</p>
        <p>اگر این درخواست را شما ثبت نکرده‌اید، لطفاً این ایمیل را نادیده بگیرید.</p>
    </div>

@endsection
