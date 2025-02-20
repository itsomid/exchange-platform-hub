<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تغییر دستگاه شما</title>
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
        .important-info {
            font-size: 18px;
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
        تغییر دستگاه شما
    </div>
    <div class="email-body">
        <p>سلام {{ $user->name }} عزیز،</p>
        <p>ما متوجه شدیم که دستگاهی که از آن به حساب کاربری خود وارد می‌شوید تغییر کرده است. جزئیات مربوط به دستگاه جدید در زیر آمده است:</p>
        <ul>
            <li><strong>مرورگر:</strong> {{ $browser }}</li>
            <li><strong>سیستم‌عامل:</strong> {{ $platform }}</li>
            <li><strong>دستگاه:</strong> {{ $device }}</li>
        </ul>
        <div class="important-info">
            اگر این تغییر توسط شما انجام نشده است، لطفاً فوراً حساب کاربری خود را ایمن کنید.
        </div>
        <p>در صورت نیاز به راهنمایی بیشتر، با تیم پشتیبانی ما تماس بگیرید.</p>
    </div>
    <div class="email-footer">
        با احترام،<br>
        تیم Bitexroom
    </div>
</div>
</body>
</html>
