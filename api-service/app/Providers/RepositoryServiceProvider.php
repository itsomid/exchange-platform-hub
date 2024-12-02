<?php

namespace App\Providers;

use App\Repositories\Interfaces\UserFinancialBlockRepositoryInterface;
use App\Repositories\ReferralCodeRepository;
use App\Repositories\Interfaces\ReferralCodeRepositoryInterface;
use App\Repositories\Interfaces\UserEmailVerificationInterface;
use App\Repositories\UserEmailVerificationRepository;
use App\Repositories\UserFinancialBlockRepository;
use App\Repositories\UserRepository;
use App\Repositories\Interfaces\UserRepositoryInterface;
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
        app()->bind(UserFinancialBlockRepositoryInterface::class, UserFinancialBlockRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}
}
