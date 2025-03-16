<?php

use App\Services\Spot\OrderMatchingEngine;
use Illuminate\Support\Facades\Route;

Route::get('/mehdi', function () {
    $om = resolve(OrderMatchingEngine::class);

    $om->processOrder();
});
