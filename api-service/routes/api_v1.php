<?php

use App\Http\Controllers\V1\Currency\ConfigController;
use App\Http\Controllers\V1\Wallet\WalletController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Http\Controllers\V1\Stock\StockContractController;
use App\Http\Controllers\V1\Stock\StockTradeController;
use App\Http\Controllers\V1\Stock\StockController;




Route::get('/captcha', [\App\Http\Controllers\CaptchaController::class, '__invoke'])->withoutMiddleware(['auth:sanctum', 'verified']);

Route::prefix('/profile')->group(function () {
    Route::get('/show', [\App\Http\Controllers\V1\Profile\UserController::class, 'show'])->name('user.show');
    Route::patch('/change-password', [\App\Http\Controllers\V1\Profile\UserController::class, 'changePassword'])->name('user.change-password');
    Route::patch('/update-profile', [\App\Http\Controllers\V1\Profile\UserController::class, 'updateProfile'])->name('user.update-profile');
    Route::get('/sessions/active', [\App\Http\Controllers\V1\Profile\SessionController::class, 'active'])->name('user.active-sessions');
});

// ReferralCode
Route::prefix('/referral-codes')->group(function () {
    Route::post('/', [App\Http\Controllers\V1\User\ReferralCodeController::class, 'store'])->name('referral-codes.store');
    Route::get('/', [App\Http\Controllers\V1\User\ReferralCodeController::class, 'lists'])->name('referral-codes.lists');

    Route::get('/referred-users/{referralCode}', [\App\Http\Controllers\V1\User\ReferralCodeUsageController::class, 'registeredUsers'])->name('referral-codes-usage.registered-users');
    Route::get('/referred-users/{userId}/owner-profits', [\App\Http\Controllers\V1\User\ReferralCodeUsageController::class, 'ownerProfits'])->name('referral-codes-usage.profits');
});

// Currency
Route::prefix('/currencies')->group(function () {
    Route::get('/deposit-withdraw-config', [ConfigController::class, 'depositWithdrawConfig'])->name('currencies.deposit-withdraw-config');
    Route::get('/all-deposit-withdraw-config', [ConfigController::class, 'allDepositWithdrawConfig'])->name('currencies.all-deposit-withdraw-config');
});

// Wallet
Route::prefix('/wallets')->group(function () {
    Route::post('/generate-address', [WalletController::class, 'generateAddress'])->name('wallets.generate-address');
    Route::post('/refresh', [WalletController::class, 'refresh'])->name('wallets.refresh')->middleware(['throttle:wallet-check']);
    Route::get('/lists', [WalletController::class, 'lists'])->name('wallets.lists');
    Route::get('/value-usdt', [WalletController::class, 'assetsUSDTValue'])->name('wallets.value-usdt');

    Route::get('/withdrawals', [\App\Http\Controllers\V1\Wallet\WithdrawController::class, 'lists'])->name('wallets.withdrawals');
    Route::post('/withdrawal', [\App\Http\Controllers\V1\Wallet\WithdrawController::class, '__invoke'])->name('wallets.withdraw')->middleware([\App\Http\Middleware\FinancialWithdrawalBlockMiddleware::class]);

    Route::get('/check-withdrawal-limit', [\App\Http\Controllers\V1\Wallet\WithdrawController::class, 'checkWithdrawalLimit']);

    Route::get('/{currencySymbol}', [WalletController::class, 'show'])->name('wallets.show');
});
Route::prefix('saved-addresses')->group(function () {
    Route::get('/addresses', [\App\Http\Controllers\V1\Wallet\SavedAddressController::class, 'lists']);
    Route::post('/addresses', [\App\Http\Controllers\V1\Wallet\SavedAddressController::class, 'save']);
    Route::delete('/addresses/{savedAddressId}', [\App\Http\Controllers\V1\Wallet\SavedAddressController::class, 'delete']);
});
// Transaction
Route::prefix('transactions')->group(function () {
    Route::get('/all-deposit-withdraw', [\App\Http\Controllers\V1\Transaction\TransactionController::class, 'allDepositWithdraw'])->name('transactions.all-deposit-withdraw');
});
// Portfolio
Route::prefix('/portfolio')->group(function () {
    Route::get('/last-week', [\App\Http\Controllers\V1\Wallet\PortfolioController::class, 'getPortfolioLastWeek'])->name('portfolio.get-portfolio-last-week');
    Route::get('/last-24-hours', [\App\Http\Controllers\V1\Wallet\PortfolioController::class, 'getPortfolio24Hours'])->name('portfolio.get-portfolio-24-hours');
});
// OTC
Route::prefix('/otc')->group(function () {
    // get-markets
    Route::get('/markets', [\App\Http\Controllers\V1\OTC\MarketController::class, 'lists'])->name('otc.markets')->withoutMiddleware(['auth:sanctum', 'verified']);

    Route::post('/buy', [\App\Http\Controllers\V1\OTC\BuyController::class, 'create'])->name('otc.buy')->middleware(['throttle:' . config('bitexroom.otc.buy_attempts.max_attempts') . ',' . config('bitexroom.otc.buy_attempts.minutes'), \App\Http\Middleware\FinancialTradeBlockMiddleware::class]);
    Route::post('/sell', [\App\Http\Controllers\V1\OTC\SellController::class, 'create'])->name('otc.sell')->middleware(['throttle:' . config('bitexroom.otc.sell_attempts.max_attempts') . ',' . config('bitexroom.otc.sell_attempts.minutes'), \App\Http\Middleware\FinancialTradeBlockMiddleware::class]);

    Route::get('/fee', [\App\Http\Controllers\V1\OTC\SettingController::class, 'fee']);

    Route::get('order-histories', [\App\Http\Controllers\V1\OTC\OrderController::class, 'lists']);
});

Route::prefix('authorization')->group(function () {
    Route::post('/otp-code/{action}', [\App\Http\Controllers\V1\Authorization\EmailOTPController::class, 'send'])->middleware(['throttle:1,1', \App\Http\Middleware\FinancialWithdrawalBlockMiddleware::class]);
});

// Notifications
Route::prefix('notifications')->group(function () {
    Route::get('/', [App\Http\Controllers\V1\User\NotificationController::class, 'index']);
    Route::post('/mark-as-read/all', [App\Http\Controllers\V1\User\NotificationController::class, 'markAsReadAll']);
    Route::post('/{id}/mark-as-read', [App\Http\Controllers\V1\User\NotificationController::class, 'markAsRead']);
    Route::get('/unread', [App\Http\Controllers\V1\User\NotificationController::class, 'unread']);
    Route::get('/count-unread', [App\Http\Controllers\V1\User\NotificationController::class, 'countUnread']);
});
// Tickets
Route::prefix('/tickets')->group(function () {
    Route::post('/', [App\Http\Controllers\V1\User\TicketController::class, 'store']);
    Route::get('/', [App\Http\Controllers\V1\User\TicketController::class, 'index']);
    Route::get('/{ticket}', [App\Http\Controllers\V1\User\TicketController::class, 'show']);
    Route::post('/{ticket}/reply', [App\Http\Controllers\V1\User\TicketController::class, 'reply']);
});


// Spot
Route::prefix('/spot')->group(function () {
    // get-markets
    Route::get('/markets', [\App\Http\Controllers\V1\Spot\MarketController::class, 'lists'])->name('spot.markets')->withoutMiddleware(['auth:sanctum', 'verified']);

    Route::get('/markets/state/{marketId}', [\App\Http\Controllers\V1\Spot\MarketController::class, 'getState']);
    Route::prefix('/orders')->group(function () {
        Route::post('/', [\App\Http\Controllers\V1\Spot\OrderController::class, 'store']);
        Route::get('/', [\App\Http\Controllers\V1\Spot\OrderController::class, 'lists']);
        Route::get('/{order}', [\App\Http\Controllers\V1\Spot\OrderController::class, 'show']);
        Route::post('/cancel/{order}', [\App\Http\Controllers\V1\Spot\OrderController::class, 'cancel']);
    });
    Route::get('/order-books/{marketId}', [\App\Http\Controllers\V1\Spot\OrderController::class, 'getOrderBooks'])->name('spot.order-books');

    Route::get('/trades/{marketId}/latest', [\App\Http\Controllers\V1\Spot\TradeController::class, 'getLatestMatched']);
});

// Stock Trading
Route::prefix('/stocks')->group(function () {
    // Get user's active contracts
    Route::get('/list', [StockController::class, 'index']);
    Route::get('/contracts', [StockContractController::class, 'index'])
        ->name('stocks.contracts');

    // Stock purchase
    Route::post('/buy', [StockTradeController::class, 'buy'])
        ->name('stocks.buy')
        ->middleware([\App\Http\Middleware\CompleteProfileMiddleware::class]);

    // Stock sale
    Route::post('/contracts/{contractId}/sell', [StockTradeController::class, 'sell'])
        ->name('stocks.sell');

    // Generate contract PDF
    Route::post('/contracts/{contractId}/generate-pdf', [StockContractController::class, 'generatePdf'])
        ->name('stocks.contracts.generate-pdf');
});
