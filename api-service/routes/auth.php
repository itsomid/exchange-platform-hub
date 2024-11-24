<?php

use Illuminate\Support\Facades\Route;

//Register routes
Route::post('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'register'])->name('register')->middleware(['throttle:auth-actions']);
Route::post('/email/verify', [\App\Http\Controllers\Auth\EmailVerificationController::class, '__invoke'])->name('email.verify')->middleware(['auth:sanctum', 'throttle:3,1']);
//Login Routes
Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login'])->name('login')->middleware(['throttle:auth-actions']);
Route::post('/two-factor/validate', [\App\Http\Controllers\Auth\LoginController::class, 'validateTwoFactor'])->name('login.two-factor')->middleware(['auth:sanctum', 'throttle:3,1']);
//Forget Password
Route::post('/forgot-password', [\App\Http\Controllers\Auth\ForgetPasswordController::class, 'sendEmail'])->name('password.email')->middleware(['guest', 'throttle:3,1']);
Route::post('/reset-password', [\App\Http\Controllers\Auth\ForgetPasswordController::class, 'resetPassword'])->name('password.update')->middleware(['guest', 'throttle:3,1']);
