<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // Inspect the current URL or route
        $currentRouteName = $request->route() ? $request->route()->getName() : null;

        // Conditional redirection based on URL or route
        if (str_contains($currentRouteName, 'admin')) {
            return url('/admin/login');
        } elseif (str_contains($currentRouteName, 'user')) {
            return url('user/auth/otp/login');
        } else {
            // Default redirection
            return url('/');
        }
    }
}
