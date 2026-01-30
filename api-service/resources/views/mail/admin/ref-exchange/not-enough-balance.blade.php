@extends('mail.layout.master-mail-layout')
@section('title', 'عدم موجودی صرافی مرجع')
@section('header')
    عدم موجودی صرافی مرجع {{ $exchangeName }} برای تکمیل معامله
@endsection
@section('content')
    <div style="text-align: right"> سلام مدیر عزیز،</div>
    <br>

    <div style="text-align: right">
        {{ $messageText }}
    </div>
    <br>

    <div style="text-align: right">
        <strong>جزئیات:</strong>
        <ul style="text-align: right; direction: rtl;">
            <li>صرافی مرجع: {{ $exchangeName }}</li>
            <li>ارز: {{ $marketName }}</li>
            <li>مقدار: {{ $amount }}</li>
            <li>نوع معامله: {{ $orderType === 'sell' ? 'فروش' : 'خرید' }}</li>
        </ul>
    </div>

@endsection
