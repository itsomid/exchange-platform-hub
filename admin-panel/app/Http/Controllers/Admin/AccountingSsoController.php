<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class AccountingSsoController extends Controller
{
    public function login(): RedirectResponse
    {
        $email = auth('admin')->user()?->email;

        abort_unless($email, 403, 'ایمیل ادمین برای ورود به پنل حسابداری ثبت نشده است.');

        $secret = config('accounting.secret');
        $baseUrl = rtrim((string) config('accounting.url'), '/');

        abort_unless($secret && $baseUrl, 500, 'تنظیمات ورود به پنل حسابداری کامل نیست.');

        $time = now()->timestamp;
        $hash = hash_hmac('sha256', $email.$time, $secret);
        $path = '/'.ltrim((string) config('accounting.login_path', '/sso/login'), '/');

        $url = $baseUrl.$path.'?'.http_build_query([
            'email' => $email,
            'time' => $time,
            'hash' => $hash,
        ]);

        return redirect()->away($url);
    }
}
