@extends('mail.layout.master-mail-layout')
@section('title', 'سفارش خرید شما تکمیل شد')
@section('header')
    مشکل در انجام معامله به علت خطای کوینکس
@endsection
@section('content')
    <div style="text-align: right"> سلام مدیر عزیز،</div>
    <br>

    <div style="text-align: right">
        قیمت سفارش با آخرین قیمت بازار اختلاف زیادی دارد.
        <p>Message: {{$messageText}}</p>
        <p>Market: {{$marketName}}</p>
        <p>Amount: {{$amount}}</p>
    </div>

@endsection
