@extends('mail.layout.master-mail-layout')
@section('title', $title)
@section('header')
    {{ $title }}
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
            @foreach ($details as $label => $value)
                <li>{{ $label }}: {{ $value }}</li>
            @endforeach
        </ul>
    </div>

@endsection
