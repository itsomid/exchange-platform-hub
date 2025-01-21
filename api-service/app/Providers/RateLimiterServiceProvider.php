<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
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

            return Limit::perMinute(config('auth.rate-limiter.too-many'))->by($key);
        });

        RateLimiter::for('wallet-check', function (Request $request) {
            // Use email if provided; otherwise, fallback to IP
            if ($request->hasAny(['currency_symbol', 'chain_symbol'])) {
                $key = $request->input('currency_symbol').$request->input('chain_symbol');
            } else {
                $key = $request->ip();
            }

            return Limit::perMinutes(
                config('bitexroom.wallet_refresh.minutes'),
                App::isLocal() ? 100 : config('bitexroom.wallet_refresh.max_attempts')
            )->by($key);
        });
    }
}
