<?php

namespace App\Providers;

use App\Repositories\Auth\ReferralCodeRepository;
use App\Repositories\Auth\ReferralCodeRepositoryInterface;
use App\Repositories\Auth\UserEmailVerificationInterface;
use App\Repositories\Auth\UserEmailVerificationRepository;
use App\Repositories\Auth\UserRepository;
use App\Repositories\Auth\UserRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        app()->bind(UserRepositoryInterface::class, UserRepository::class);
        app()->bind(UserEmailVerificationInterface::class, UserEmailVerificationRepository::class);
        app()->bind(ReferralCodeRepositoryInterface::class, ReferralCodeRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}
}
