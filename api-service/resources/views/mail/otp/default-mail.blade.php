<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>کد دو عاملی</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    <style>
        body {
            font-family: Vazirmatn, Arial, sans-serif;
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
            text-align: center;
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
        کد تأیید شما
    </div>
    <div class="email-body">
        @if($name)
        <p>سلام {{ $name }} عزیز،</p>
        @endif
        <p>کد تأیید شما:</p>
        <div class="otp-code">{{ $code }}</div>
        <p>این کد تا <strong>{{ $expiration }}</strong> معتبر است.</p>
        <p>اگر این درخواست را شما ثبت نکرده‌اید، لطفاً این ایمیل را نادیده بگیرید.</p>
    </div>
    <div class="email-footer">
        با احترام،<br>
        تیم Bitexroom
    </div>
</div>
</body>
</html>
