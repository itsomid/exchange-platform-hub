@extends('mail.layout.master-mail-layout')
@section('title', 'سفارش خرید شما تکمیل شد')
@section('header')
    عدم موجودی کوینکس برای تکمیل معامله
@endsection
@section('content')
    <div style="text-align: right"> سلام مدیر عزیز،</div>
    <br>

    <div style="text-align: right">
        {{ $messageText }}
    </div>

@endsection
