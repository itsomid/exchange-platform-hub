<?php

use Illuminate\Support\Facades\Route;

Route::prefix('transactions')->middleware('jwt.auth')->group(function () {
    Route::get('/', [\App\Http\Controllers\Accounting\V1\TransactionController::class, 'index']);
    Route::post('/save-journal-entry-number', [\App\Http\Controllers\Accounting\V1\TransactionController::class, 'saveJournalNumber']);
});

Route::get('/currencies', [\App\Http\Controllers\Accounting\V1\CurrencyController::class, 'index']);

// Token generation route
Route::post('/token/generate', [\App\Http\Controllers\V1\Auth\TokenController::class, 'generate']);
