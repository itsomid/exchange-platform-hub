@extends('mail.layout.master-mail-layout')
@section('title', 'سفارش خرید شما تکمیل شد')
@section('header')
    عدم موجودی هات ولت
@endsection
@section('content')
    <div style="text-align: right"> سلام مدیر عزیز،</div>
    <br>

    <div style="text-align: center">
        <p>صرافی ما برای برداشت {{$currencyName}} به مقدار {{$amount}} از هات ولت کاربر {{$user->username}} (ID: {{$user->id}}) به علت عدم موجودی دچار خطا شد</p>
        <p><strong>اطلاعات کاربر:</strong></p>
        <ul style="text-align: right; display: inline-block;">
            <li>نام کاربری: {{$user->username}}</li>
            <li>شناسه کاربر: {{$user->id}}</li>
            <li>ایمیل: {{$user->email}}</li>
        </ul>
        <a href="{{ url('/transactions') }}">مشاهده تراکنش</a>
    </div>

@endsection
