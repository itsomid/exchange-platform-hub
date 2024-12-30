@extends('mail.layout.master-mail-layout')
@section('title', 'سفارش شما تکمیل شد')
@section('header')
    @if($type === 'sell')
    سفارش فروش شما تکمیل شد
    @elseif($type === 'buy')
سفارش خرید شما تکمیل شد
    @endif
@endsection
@section('content')
    <div style="text-align: right"> سلام عزیز،</div>
    <br>
    @if($type === 'sell')
        <div style="text-align: right">
            سفارش فروش شما تکمیل شد. مبلغ معادل {{$quantity}} {{$currencySymbol}} از حساب شما برداشت شد.
        </div>
    @elseif($type === 'buy')
        <div style="text-align: right">
            سفارش خرید شما تکمیل شد. مبلغ معادل {{$quantity}} {{$currencySymbol}} به حساب شما واریز شد.
        </div>
    @endif

@endsection
