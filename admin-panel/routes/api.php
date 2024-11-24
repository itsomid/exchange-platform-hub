<?php

use App\Http\Controllers\User\UserNoteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::name('api.')->group(callback: function () {
    Route::get('/users/{user}/support_description', [UserNoteController::class, 'note'])->name('user.get-note');
    Route::patch('/users/{user}/update_support_description', [UserNoteController::class, 'updateNote'])->name('user.update-note');
});
