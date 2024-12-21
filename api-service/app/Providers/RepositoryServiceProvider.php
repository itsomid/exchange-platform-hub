<?php

namespace App\Providers;

use App\Repositories\CurrencyRepository;
use App\Repositories\DepositRepository;
use App\Repositories\EmailOTPRepository;
use App\Repositories\Interfaces\CurrencyRepositoryInterface;
use App\Repositories\Interfaces\DepositRepositoryInterface;
use App\Repositories\Interfaces\EmailOTPRepositoryInterface;
use App\Repositories\Interfaces\ReferralCodeRepositoryInterface;
use App\Repositories\Interfaces\ReferralCodeUsageRepositoryInterface;
use App\Repositories\Interfaces\UserEmailVerificationInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\WalletChainRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Repositories\ReferralCodeRepository;
use App\Repositories\ReferralCodeUsageRepository;
use App\Repositories\UserEmailVerificationRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletChainRepository;
use App\Repositories\WalletRepository;
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
        app()->bind(CurrencyRepositoryInterface::class, CurrencyRepository::class);
        app()->bind(WalletRepositoryInterface::class, WalletRepository::class);
        app()->bind(WalletChainRepositoryInterface::class, WalletChainRepository::class);
        app()->bind(DepositRepositoryInterface::class, DepositRepository::class);
        app()->bind(ReferralCodeRepositoryInterface::class, ReferralCodeRepository::class);
        app()->bind(ReferralCodeUsageRepositoryInterface::class, ReferralCodeUsageRepository::class);
        app()->bind(EmailOTPRepositoryInterface::class, EmailOTPRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}
}
