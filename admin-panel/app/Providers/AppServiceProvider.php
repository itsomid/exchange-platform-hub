<?php

namespace App\Providers;

use App\Models\User;
use App\Repositories\ExchangeRepository;
use App\Repositories\LockedBalanceRepository;
use App\Repositories\WalletRepository;
use App\Repositories\MarketRepository;
use App\Repositories\Interfaces\ExchangeRepositoryInterface;
use App\Repositories\Interfaces\LockedBalanceRepositoryInterface;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Facades\Pulse;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
        $this->app->bind(MarketRepositoryInterface::class, MarketRepository::class);
        $this->app->bind(ExchangeRepositoryInterface::class, ExchangeRepository::class);
        $this->app->bind(WalletRepositoryInterface::class, WalletRepository::class);
        $this->app->bind(LockedBalanceRepositoryInterface::class, LockedBalanceRepository::class);
    }

    public function boot(): void
    {
        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }
        // Pulse's own gate allows everyone in local; deny by default so only the Spatie permission grants access.
        Gate::define('viewPulse', fn () => false);
        // Pulse data is recorded by api-service, so its user ids belong to App\Models\User, not Admin.
        Pulse::users(fn ($ids) => User::whereIn('id', $ids)->get()->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->fullname(),
            'extra' => $user->email,
        ]));
        //
        Paginator::defaultView('dashboard.layout.vendor.vuexy_pagination');

        View::composer(
            ['dashboard.layout.sidebar', 'dashboard.layout.navbar', 'dashboard.layout.security_banner'],
            function ($view) {
                $admin = Auth::guard('admin')->user();
                $requiresTwoFactor = (bool) $admin?->mustSetUpTwoFactor();
                $passwordDeadline = $admin?->passwordChangeDeadline();
                $passwordOverdue = (bool) $passwordDeadline?->isPast();

                $view->with([
                    'requiresTwoFactor' => $requiresTwoFactor,
                    'passwordDeadline' => $passwordDeadline,
                    'passwordOverdue' => $passwordOverdue,
                    // Panel is locked down to the profile pages
                    'panelLocked' => $requiresTwoFactor || $passwordOverdue,
                ]);
            }
        );
        View::composer('dashboard.layout.sidebar', function ($view) {
            $admin = Auth::guard('admin')->user();
            $unreadCount = $admin ? $admin->unreadNotifications->count() : 0;
            $view->with('unreadCount', $unreadCount);
        });
        View::composer('dashboard.layout.navbar', function ($view) {
            $admin = auth()->user(); // Get authenticated admin
            $notifications = $admin->unreadNotifications;
            $unreadCount = $admin ? $admin->unreadNotifications->count() : 0;
            $view->with(['unreadCount' => $unreadCount, 'notifications' => $notifications]);
        });
    }
}
