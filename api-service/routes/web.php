<?php

use App\Services\Spot\OrderMatchingEngine;
use Illuminate\Support\Facades\Route;

Route::get('/mehdi', function () {
    $om = resolve(OrderMatchingEngine::class);

    $om->processOrder();
});

Route::get('/test-mehdi', function () {
    $so = resolve(\App\Services\Spot\SpotService::class);
    $res = $so->getLatestOrderBook(1, config('spot.order_book_limit_count'));

    return $res;
});

Route::view('/web-socket', 'welcome');
Route::get('test-dis', function () {
    \App\Events\OrderBookUpdated::dispatch(1);
});
