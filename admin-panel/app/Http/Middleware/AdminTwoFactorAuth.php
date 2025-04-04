<?php

namespace App\Http\Middleware;

use App\Functions\FlashMessages\Toast;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminTwoFactorAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if admin is authenticated
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();

            // Check if 2FA is enabled for the admin
            if (empty($admin->two_factor_secret) && app()->environment() == 'production') {
                // If 2FA is not enabled, show the 2FA required page
                Toast::message('دسترسی به این بخش نیاز به فعال سازی احراز هویت دو مرحله‌ای دارد.')->danger()->notify();

                // Return the 2FA required view with the layout
                return redirect()->route('admin.profile.2fa.edit');
            }
        }

        return $next($request);
    }
}
