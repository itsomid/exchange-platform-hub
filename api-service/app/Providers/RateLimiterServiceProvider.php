<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimiterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Shared Rate Limiter for Login and Register
        RateLimiter::for('auth-actions', function ($request) {
            // Use email if provided; otherwise, fallback to IP
            $key = $request->input('email') ?? $request->ip();

            return Limit::perMinute(config('auth.rate-limiter.too-many'))->by($key)
                ->response(function () {
                    return response()->json([
                        'message' => __('auth.too_many_attempts'),
                    ], 429);
                });
        });
    }
}
