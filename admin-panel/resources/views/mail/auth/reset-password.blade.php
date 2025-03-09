<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بازیابی رمز عبور</title>
    <style>

        body {
            font-family: -apple-system,BlinkMacSystemFont,segoe ui,Roboto,Helvetica,Arial,sans-serif;
            direction: rtl;
            background-color: #f7f7f7;
            margin: 0;
            padding: 0;
            color: #333;
            line-height: 1.6;
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

        .email-body p {
            margin: 10px 0;
        }

        .reset-password-link {
            text-align: center;
            margin: 20px 0;
        }

        .reset-password-link a {
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
        بازیابی رمز عبور
    </div>
    <div class="email-body">
        <p>سلام کاربر عزیز،</p>
        <p>لینک بازیابی رمز عبور توسط ادمین سیستم به شما ارسال شد. برای تنظیم مجدد رمز عبور خود، لطفاً روی دکمه زیر کلیک کنید:</p>
        <div class="reset-password-link">
            <a href="{{ $url }}" target="_blank">بازیابی رمز عبور</a>
        </div>
        <p>این لینک تا <strong>{{ $expiration }}</strong> معتبر است. اگر شما این درخواست را نداده‌اید، لطفاً این ایمیل را نادیده بگیرید.</p>
        <p>با احترام،<br>تیم Bitexroom</p>
    </div>
    <div class="email-footer">
        <p>تمام حقوق محفوظ است &copy; Bitexroom</p>
    </div>
</div>
</body>
</html>
