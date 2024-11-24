<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }
        //        Gate::define('viewPulse', function (Admin $admin) {
        //            return true;
        //        });
        //
        Paginator::defaultView('dashboard.layout.vendor.vuexy_pagination');
    }
}
