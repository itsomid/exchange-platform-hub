<?php

namespace App\Providers;

use App\Listeners\PrepareMailDelivery;
use App\Mail\EmailVerificationMail;
use App\Mail\LoggingMailManager;
use App\Models\SpotOrder;
use App\Models\User;
use App\Services\Bot\ReferenceExchange\BinanceBotAdapter;
use App\Services\Bot\ReferenceExchange\ExchangeContract;
use App\Services\Bot\ReferenceExchange\FakeBotExchange;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->extend('mail.manager', fn ($manager, $app) => new LoggingMailManager($app));

        $this->app->bind(ExchangeContract::class, function () {
            if (config('smart-bot.exchange_driver') === 'fake') {
                return new FakeBotExchange();
            }

            return new BinanceBotAdapter();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        $this->loadMigrationsFrom(getcwd().'/../admin-panel/database/migrations');

        Event::listen(MessageSending::class, PrepareMailDelivery::class);

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
