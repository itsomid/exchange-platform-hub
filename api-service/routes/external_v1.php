<?php

use App\Http\Controllers\External\V1\UserBalanceController;
use App\Http\Controllers\External\V1\UserInquiryController;
use App\Http\Controllers\External\V1\UserCreditController;
use App\Http\Controllers\External\V1\StockController;
use App\Http\Controllers\External\V1\TrackingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| External API Routes (Version 1)
|--------------------------------------------------------------------------
|
| These routes are for external systems to interact with the platform.
| All routes require API system authentication via middleware.
|
*/

Route::middleware(['api.system.auth'])->group(function () {

    // Tracking Endpoints
    Route::prefix('/tracking')->group(function () {
        Route::post('/generate', [TrackingController::class, 'generateTrackingCode'])
            ->name('external.tracking.generate');

        Route::post('/requests', [TrackingController::class, 'getRequestsByTrackingCode'])
            ->name('external.tracking.requests');
    });

    // User Balance Endpoints
    Route::prefix('/users')->group(function () {
        Route::post('/balance', [UserBalanceController::class, 'getUserBalance'])
            ->name('external.users.balance');

        Route::post('/balances', [UserBalanceController::class, 'getUserBalances'])
            ->name('external.users.balances');

        // User Inquiry Endpoints
        Route::post('/inquiry', [UserInquiryController::class, 'checkUserExists'])
            ->name('external.users.inquiry');

        Route::post('/inquiries', [UserInquiryController::class, 'checkMultipleUsersExist'])
            ->name('external.users.inquiries');

        // User Credit Endpoints
        Route::post('/credit/increase', [UserCreditController::class, 'increaseCredit'])
            ->name('external.users.credit.increase');

        Route::post('/credit/transactions', [UserCreditController::class, 'getCreditTransactions'])
            ->name('external.users.credit.transactions');
    });

    // Stock Endpoints
    Route::prefix('/stocks')->group(function () {
        Route::post('/purchase', [StockController::class, 'purchaseStock'])
            ->name('external.stocks.purchase');
        Route::post('/purchased/transactions', [StockController::class, 'getPurchasedStocks'])
            ->name('external.stocks.purchased.transactions');
    });
});
