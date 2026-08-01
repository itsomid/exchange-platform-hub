<?php

use App\Http\Controllers\Internal\BotAdminController;
use App\Http\Controllers\Internal\BotTestController;
use Illuminate\Support\Facades\Route;

/*
| Internal admin Test-Lab endpoints. Protected by `bot-test-auth` middleware
| (shared X-Internal-Token). Mounted at /api/internal/bot-test/* — see
| bootstrap/app.php.
*/

Route::prefix('bot-test')->middleware('bot-test-auth')->group(function () {
    Route::post('start',       [BotTestController::class, 'start']);
    Route::get ('status',      [BotTestController::class, 'status']);
    Route::post('bump-price',  [BotTestController::class, 'bumpPrice']);
    Route::post('set-price',   [BotTestController::class, 'setPrice']);
    Route::post('sync',        [BotTestController::class, 'sync']);
    Route::post('reset',       [BotTestController::class, 'reset']);
});

/*
| Internal admin bot operations (admin-panel → api-service). Protected by
| `bot-admin-auth` (shared X-Internal-Token) — works in production too.
*/
Route::prefix('bot-admin')->middleware('bot-admin-auth')->group(function () {
    Route::post('orders/{order}/cancel-preview',   [BotAdminController::class, 'cancelPreview']);
    Route::post('orders/{order}/cancel',           [BotAdminController::class, 'cancel']);
    Route::post('users/{user}/cancel-all-preview', [BotAdminController::class, 'cancelAllPreview']);
    Route::post('users/{user}/cancel-all',         [BotAdminController::class, 'cancelAll']);
});
