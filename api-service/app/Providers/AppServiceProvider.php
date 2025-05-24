<?php

namespace App\Providers;

use App\Mail\EmailVerificationMail;
use App\Models\SpotOrder;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        $this->loadMigrationsFrom(getcwd().'/../admin-panel/database/migrations');

        VerifyEmail::toMailUsing(function (User $notifiable, string $url) {
            $url = $notifiable->getUrlForEmailVerification(); // overwrite url

            return new EmailVerificationMail($notifiable, $url);
        });

        //Spot Order Update
        Gate::define('update-spot-order', function (User $user, SpotOrder $order) {
            return $user->id === $order->user_id;
        });
    }
}
