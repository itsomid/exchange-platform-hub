<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminTwoFactorAuth
{
    /**
     * In production only the profile pages stay reachable until the admin has activated 2FA.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();

        if ($admin?->mustSetUpTwoFactor()) {
            $message = 'برای استفاده از پنل باید در ابتدا احراز هویت دو مرحله‌ای (2FA) را فعال کنید.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'redirect' => route('admin.profile.2fa.edit'),
                ], 403);
            }

            return redirect()->route('admin.profile.2fa.edit');
        }

        return $next($request);
    }
}
