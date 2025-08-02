<?php

use App\Http\Controllers\SpotBot\SpotBotController;
use App\Http\Controllers\SpotBot\SpotBotSettingController;
use Illuminate\Support\Facades\Route;


Route::post('/token/generate', [\App\Http\Controllers\V1\Auth\TokenController::class, 'generate']);


Route::get('/status/{currency_id}', [SpotBotController::class, 'getBotStatus'])->middleware('jwt.auth');

Route::get('/settings', [SpotBotSettingController::class, 'getAllSettings'])->middleware('jwt.auth');
Route::get('/settings/{currency_id}', [SpotBotSettingController::class, 'getSettings'])->middleware('jwt.auth');

Route::post('/generate-orders/{currency_id}', [SpotBotController::class, 'generateOrders'])->middleware('jwt.auth');
Route::post('/cancel-orders/{currency_id}', [SpotBotController::class, 'cancelOrders'])->middleware('jwt.auth');

Route::post('/sync-orders/{currency_id}', [SpotBotController::class, 'syncOrders'])->middleware('jwt.auth');
Route::post('/match-order/{currency_id}', [SpotBotController::class, 'matchOrder'])->middleware('jwt.auth');

Route::post('/sync-orders', [SpotBotController::class, 'syncAllOrders'])->middleware('jwt.auth');
Route::post('/cancel-all-orders', [SpotBotController::class, 'cancelAllOrders'])->middleware('jwt.auth');
Route::post('/generate-orders', [SpotBotController::class, 'generateAllOrders'])->middleware('jwt.auth');
