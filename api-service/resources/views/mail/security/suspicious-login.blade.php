@extends('mail.layout.master-mail-layout')

@section('title', 'هشدار امنیتی: ورود مشکوک')

@section('header')
هشدار امنیتی: ورود مشکوک به حساب کاربری
@endsection

@section('content')
<p>سلام {{ $data['user']->name }}،</p>

<p>یک ورود مشکوک به حساب کاربری شما در سیستم تشخیص داده شده است.</p>

<h3>جزئیات ورود:</h3>
<ul>
    <li><strong>زمان:</strong> {{ $data['time'] }}</li>
    <li><strong>آدرس IP:</strong> {{ $data['ip'] }}</li>
    <li><strong>دستگاه:</strong> {{ $data['user_agent'] }}</li>
</ul>

<h3>دلایل تشخیص فعالیت مشکوک:</h3>
<ul>
    @foreach($data['activities'] as $activity)
        @if($activity['type'] == 'user_agent_changed')
            <li>
                <strong>تغییر دستگاه یا مرورگر</strong>
                <ul>
                    <li>دستگاه قبلی: {{ $activity['old_value'] }}</li>
                    <li>دستگاه جدید: {{ $activity['new_value'] }}</li>
                </ul>
            </li>
        @elseif($activity['type'] == 'ip_changed')
            <li>
                <strong>تغییر آدرس IP</strong>
                <ul>
                    <li>آدرس قبلی: {{ $activity['old_value'] }}</li>
                    <li>آدرس جدید: {{ $activity['new_value'] }}</li>
                </ul>
            </li>
        @elseif($activity['type'] == 'unusual_time')
            <li><strong>ورود در زمان غیرمعمول</strong> ({{ $activity['time'] }})</li>
        @elseif($activity['type'] == 'multiple_sessions')
            <li><strong>ورود همزمان از چند دستگاه مختلف</strong></li>
        @elseif($activity['type'] == 'location_changed')
            <li>
                <strong>تغییر موقعیت جغرافیایی</strong>
                <ul>
                    <li>موقعیت قبلی: {{ $activity['old_location'] }}</li>
                    <li>موقعیت جدید: {{ $activity['new_location'] }}</li>
                </ul>
            </li>
        @endif
    @endforeach
</ul>

<p>اگر این ورود توسط شما انجام شده است، می‌توانید این هشدار را نادیده بگیرید.</p>

<p>اگر شما وارد سیستم نشده‌اید، لطفاً اقدامات زیر را انجام دهید:</p>
<ol>
    <li>فوراً رمز عبور خود را تغییر دهید</li>
    <li>احراز هویت دو مرحله‌ای را فعال کنید</li>
    <li>با پشتیبانی تماس بگیرید</li>
</ol>

<div class="activation-link">
    <a href="{{ config('app.url') }}/profile/security">مدیریت امنیت حساب کاربری</a>
</div>
@endsection
