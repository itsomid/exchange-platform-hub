<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\External\V1\HDWallet\DepositWebhookController; 
     
Route::post('/wallet/deposit/notify', [DepositWebhookController::class, 'notify'])->middleware('throttle:60,1');