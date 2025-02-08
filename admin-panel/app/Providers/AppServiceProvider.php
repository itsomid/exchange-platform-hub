<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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

        View::composer('dashboard.layout.sidebar', function ($view) {
            $admin = Auth::guard('admin')->user();
            $unreadCount = $admin ? $admin->unreadNotifications->count() : 0;
            $view->with('unreadCount', $unreadCount);
        });
        View::composer('dashboard.layout.navbar', function ($view) {
            $admin = auth()->user(); // Get authenticated admin
            $notifications = $admin->notifications;
            $unreadCount = $admin ? $admin->unreadNotifications->count() : 0;
            $view->with(['unreadCount' => $unreadCount, 'notifications' => $notifications]);
        });
    }
}
