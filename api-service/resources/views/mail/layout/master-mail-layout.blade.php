<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="{{ config('app.url') }}">
    <title>@yield('title')</title>

    <style>

        body {
            font-family: -apple-system,BlinkMacSystemFont,segoe ui,Roboto,Helvetica,Arial,sans-serif;
            direction: rtl;
            background-color: #f7f7f7;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        .email-header {
            background-color: #4CAF50;
            color: #ffffff;
            text-align: center;
            padding: 20px;
            font-size: 24px;
        }
        .email-body {
            padding: 20px;

        }
        .activation-code {
            font-size: 25px;
            font-weight: bold;
            color: #4CAF50;
            text-align: center;
            margin: 20px auto;
            background-color: #f1f1f1;
            width: 160px;
            padding: 10px;
        }
        .activation-link {
            display: block;
            text-align: center;
            margin: 30px 0;
        }
        .activation-link a {
            background-color: #4CAF50;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 18px;
        }
        .otp-code {
            font-size: 22px;
            font-weight: bold;
            color: #4CAF50;
            margin: 20px 0;
        }
        .email-footer {
            background-color: #f1f1f1;
            text-align: center;
            padding: 10px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="email-header">
        @yield('header')
    </div>
    <div class="email-body">
        @yield('content')
        <p>با احترام،<br>تیم Bitexroom</p>
    </div>
    <div class="email-footer">
        <p>تمام حقوق محفوظ است &copy; <a href="{{ config('app.url') }}" style="color: #666; text-decoration: none;">Bitexroom</a></p>
        <p>{{config('app.url')}}</p>
    </div>
</div>
</body>
</html>
