<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    {{ $head ?? '' }}
</head>
<body>
<div class="email-container">
    <div class="email-header">
        {{ $header ?? '' }}
    </div>
    <div class="email-body">
        {{ Illuminate\Mail\Markdown::parse($slot) }}
    </div>
    <div class="email-footer">
        با احترام،<br>
        تیم Bitexroom
    </div>
</div>
</body>
</html>
