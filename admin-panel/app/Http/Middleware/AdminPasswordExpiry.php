<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminPasswordExpiry
{
    /**
     * While the grace period is running nothing is restricted. Once it is over,
     * only the profile pages stay reachable until the password is changed.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();

        if ($admin?->passwordChangeOverdue()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'مهلت تغییر گذرواژه تمام شده است. برای استفاده از پنل باید گذرواژه خود را تغییر دهید.',
                    'redirect' => route('admin.profile.password.edit'),
                ], 403);
            }

            return redirect()->route('admin.profile.password.edit');
        }

        return $next($request);
    }
}
