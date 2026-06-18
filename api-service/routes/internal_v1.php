<?php

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
