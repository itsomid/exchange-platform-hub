<?php

use Illuminate\Support\Facades\Route;

Route::prefix('transactions')->group(function () {

    Route::get('/', [\App\Http\Controllers\Accounting\V1\TransactionController::class, 'index']);
    Route::post('/save-journal-entry-number', [\App\Http\Controllers\Accounting\V1\TransactionController::class, 'saveJournalNumber']);
});
