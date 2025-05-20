<?php

namespace App\Providers;

use App\Repositories\CurrencyRepository;
use App\Repositories\DepositRepository;
use App\Repositories\EmailOTPRepository;
use App\Repositories\ExchangeRepository;
use App\Repositories\Interfaces\CurrencyRepositoryInterface;
use App\Repositories\Interfaces\DepositRepositoryInterface;
use App\Repositories\Interfaces\EmailOTPRepositoryInterface;
use App\Repositories\Interfaces\ExchangeRepositoryInterface;
use App\Repositories\Interfaces\LockedBalanceRepositoryInterface;
use App\Repositories\Interfaces\MarketHistoryRepositoryInterface;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use App\Repositories\Interfaces\OTCOrderRepositoryInterface;
use App\Repositories\Interfaces\OTCRefExchangeWithdrawalInterface;
use App\Repositories\Interfaces\ReferralCodeRepositoryInterface;
use App\Repositories\Interfaces\ReferralCodeUsageRepositoryInterface;
use App\Repositories\Interfaces\SpotOrderRepositoryInterface;
use App\Repositories\Interfaces\SpotTradeRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\UserEmailVerificationInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\WalletChainRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Repositories\Interfaces\WithdrawalRepositoryInterface;
use App\Repositories\LockedBalanceRepository;
use App\Repositories\MarketHistoryRepository;
use App\Repositories\MarketRepository;
use App\Repositories\OTCOrderRepository;
use App\Repositories\OTCRefExchangeWithdrawalRepository;
use App\Repositories\ReferralCodeRepository;
use App\Repositories\ReferralCodeUsageRepository;
use App\Repositories\SpotOrderRepository;
use App\Repositories\SpotTradeRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserEmailVerificationRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletChainRepository;
use App\Repositories\WalletRepository;
use App\Repositories\WithdrawalRepository;
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
        app()->bind(MarketHistoryRepositoryInterface::class, MarketHistoryRepository::class);
        app()->bind(MarketRepositoryInterface::class, MarketRepository::class);
        app()->bind(TransactionRepositoryInterface::class, TransactionRepository::class);
        app()->bind(OTCOrderRepositoryInterface::class, OTCOrderRepository::class);
        app()->bind(WithdrawalRepositoryInterface::class, WithdrawalRepository::class);
        app()->bind(OTCRefExchangeWithdrawalInterface::class, OTCRefExchangeWithdrawalRepository::class);
        app()->bind(LockedBalanceRepositoryInterface::class, LockedBalanceRepository::class);
        app()->bind(SpotOrderRepositoryInterface::class, SpotOrderRepository::class);
        app()->bind(SpotTradeRepositoryInterface::class, SpotTradeRepository::class);
        app()->bind(ExchangeRepositoryInterface::class, ExchangeRepository::class);

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}
}
