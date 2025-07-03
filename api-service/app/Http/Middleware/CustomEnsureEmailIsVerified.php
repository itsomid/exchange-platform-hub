<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;

class CustomEnsureEmailIsVerified
{
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if (! $request->user() ||
            ($request->user() instanceof MustVerifyEmail &&
            ! $request->user()->hasVerifiedEmail())) {
            return $request->expectsJson()
                ? response()->json([
                    'message' => __('حساب کاربری شما تایید نشده است'),
                    'error_code' => 'EMAIL_NOT_VERIFIED'
                ], 403)
                : Redirect::guest(URL::route($redirectToRoute ?: 'verification.notice'));
        }
        return $next($request);
    }
}
