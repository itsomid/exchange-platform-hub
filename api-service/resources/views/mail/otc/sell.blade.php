@extends('mail.layout.master-mail-layout')
@section('title', 'سفارش خرید شما تکمیل شد')
@section('header')
    سفارش فروش شما تکمیل شد
@endsection
@section('content')
    <div style="text-align: right"> سلام کاربر عزیز،</div>
    <br>

    <div style="text-align: right">
        درخواست فروش سریع {{ $currencySymbol }} به مقدار {{ $amount }} با موفقیت انجام شد.
    </div>

@endsection
