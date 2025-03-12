@extends('mail.layout.master-mail-layout')
@section('title', 'سفارش خرید شما تکمیل شد')
@section('header')
    برداشت موفق
@endsection
@section('content')
    <div style="text-align: right"> سلام کاربر عزیز،</div>
    <br>

    <div style="text-align: center">
        <p>برداشت {{ $currencySymbol }} به مقدار {{ $amount }} روی شبکه {{ $network }} با موفقیت انجام شد.</p>
        <p>متشکریم که از پلتفرم ما استفاده می کنید!</p>
        <a href="{{ url('/transactions') }}">مشاهده تراکنش</a>
    </div>

@endsection
