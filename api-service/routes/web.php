<?php

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;
use Mews\Captcha\Facades\Captcha;

Route::get('/mehdi', function () {
    $a = \App\Models\Admin::query()->role('super_admin')->get();
    dd($a);
});

Route::get('/img-captcha', function () {
    $captcha = Captcha::create('flat', api: true);
    //    $response = ['is_fishy' => true,  'image' => $captcha['img'], 'captcha_key' => $captcha['key']];
    echo "<img src='{$captcha['img']}'><br>";
    echo $captcha['key'];
    $response = ['is_fishy' => true,  'image' => $captcha['img'], 'captcha_key' => $captcha['key']];
    //    return response($response, Response::HTTP_OK);

});
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    //go to vuejs
})->middleware(['auth', 'signed'])->name('verification.verify');
