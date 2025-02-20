<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فعال‌سازی حساب کاربری</title>
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
            text-align: right;
        }
        .activation-code {
            font-size: 22px;
            font-weight: bold;
            color: #4CAF50;
            text-align: center;
            margin: 20px 0;
        }
        .activation-link {
            display: block;
            text-align: center;
            margin: 20px 0;
        }
        .activation-link a {
            background-color: #4CAF50;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 18px;
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
        فعال‌سازی حساب کاربری
    </div>
    <div class="email-body">
        <p>سلام {{ $user->name }} عزیز،</p>
        <p>از ثبت‌نام شما در <strong>بیتکس روم</strong> سپاسگزاریم! برای فعال‌سازی حساب خود، لطفاً از کد فعال‌سازی زیر استفاده کنید:</p>
        <div class="activation-code">{{ $token }}</div>
        <p>همچنین می‌توانید با کلیک روی دکمه زیر حساب خود را فعال کنید:</p>
        <div class="activation-link">
            <a href="{{ $url }}">فعال‌سازی حساب</a>
        </div>
        <p>توجه داشته باشید که کد و لینک فعال‌سازی تا <strong>{{ $expirationDate }}</strong> معتبر است.</p>
        <p>اگر شما این درخواست را ثبت نکرده‌اید، لطفاً این ایمیل را نادیده بگیرید.</p>
        <p>ما از حضور شما در کنار خود خوشحالیم!</p>
    </div>
    <div class="email-footer">
        با احترام،<br>
        تیم Bitexroom
    </div>
</div>
</body>
</html>
