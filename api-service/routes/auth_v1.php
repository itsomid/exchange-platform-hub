<?php

use App\Http\Middleware\DecryptTokenLoginMiddleware;
use Illuminate\Support\Facades\Route;

//Register routes
Route::post('/register', [\App\Http\Controllers\V1\Auth\RegisterController::class, 'register'])->name('register')->middleware(['throttle:auth-actions']);
Route::post('/email/resend', [\App\Http\Controllers\V1\Auth\RegisterController::class, 'resend'])->name('resend')->middleware(['auth:sanctum', 'throttle:1,1']);
Route::post('/email/verify', [\App\Http\Controllers\V1\Auth\EmailVerificationController::class, '__invoke'])->name('email.verify')->middleware(['auth:sanctum', 'throttle:3,1']);
Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\V1\Auth\EmailVerificationController::class, 'verify'])
    ->middleware(['signed'])
    ->name('verification.verify');
//Login Routes
Route::post('/login', [\App\Http\Controllers\V1\Auth\LoginController::class, 'login'])->name('login')->middleware(['throttle:auth-actions']);
Route::post('/2fa/verify-login', [\App\Http\Controllers\V1\Auth\TwoFactorController::class, 'verifyLogin'])->name('2fa.verify-login')
    ->middleware([
        DecryptTokenLoginMiddleware::class,
        'throttle:3,1',
    ]);
//Forget Password
Route::post('/forgot-password', [\App\Http\Controllers\V1\Auth\ForgetPasswordController::class, 'sendEmail'])->name('password.email')->middleware(['guest', 'throttle:3,1']);
Route::post('/reset-password', [\App\Http\Controllers\V1\Auth\ForgetPasswordController::class, 'resetPassword'])->name('password.update')->middleware(['guest', 'throttle:3,1']);

Route::prefix('/2fa')->middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('setup', [\App\Http\Controllers\V1\Auth\TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::post('save-secret', [\App\Http\Controllers\V1\Auth\TwoFactorController::class, 'saveSecret'])->name('2fa.save')->middleware(\App\Http\Middleware\VerifyOTPEmailMiddleware::class.':'.\App\Enums\EmailOTPActionEnum::TWO_FACTOR_SETUP->value);
    Route::post('disable', [\App\Http\Controllers\V1\Auth\TwoFactorController::class, 'disable'])->name('2fa.disable');
});

Route::prefix('/2fa/reset')->group(function () {
    Route::post('/send-email-verification', [\App\Http\Controllers\V1\Auth\ResetTwoFactorController::class, 'sendEmailVerification'])->middleware(['guest', 'throttle:1,1']);
    Route::post('/disable', [\App\Http\Controllers\V1\Auth\ResetTwoFactorController::class, 'disable'])->middleware(['guest', 'throttle:1,1']);
});
