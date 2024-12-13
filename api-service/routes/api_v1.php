<?php

use App\Http\Controllers\V1\Currency\ConfigController;
use App\Http\Controllers\V1\Wallet\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/captcha', [\App\Http\Controllers\CaptchaController::class, '__invoke'])->withoutMiddleware(['auth:sanctum', 'verified']);
Route::prefix('/profile')->group(function () {
    Route::patch('/change-password', [\App\Http\Controllers\V1\Profile\UserController::class, 'changePassword'])->name('user.change-password');
    Route::patch('/update-profile', [\App\Http\Controllers\V1\Profile\UserController::class, 'updateProfile'])->name('user.update-profile');
});

//ReferralCode
Route::prefix('/referral-codes')->group(function () {
    Route::post('/', [App\Http\Controllers\V1\User\ReferralCodeController::class, 'store'])->name('referral-codes.store');
    Route::get('/', [App\Http\Controllers\V1\User\ReferralCodeController::class, 'lists'])->name('referral-codes.lists');
});

//Currency
Route::prefix('/currencies')->group(function () {
    Route::get('/deposit-withdraw-config', [ConfigController::class, 'depositWithdrawConfig'])->name('currencies.deposit-withdraw-config');
});

//Wallet
Route::prefix('/wallets')->group(function () {
    Route::post('/generate-address', [WalletController::class, 'generateAddress'])->name('wallets.generate-address');
});
