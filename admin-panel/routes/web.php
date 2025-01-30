<?php

use App\Models\ReferralCode;
use Illuminate\Support\Facades\Route;

//Route::view('/', 'welcome');

Route::redirect('', '/admin/login');
Route::get('/test', function () {
    return $user_id = ReferralCode::query()
        ->inRandomOrder()
        ->value('user_id');

    return \App\Models\User::query()->find($user_id);
});

Route::get('test-omid', function () {
    $asset = \App\Services\Exchanges\Asset\AssetFactory::make('coinex');

    foreach ($asset->getBalance() as $balance) {
        echo 'currency:'.$balance->getCcy().' available:'.$balance->getAvailable().' frozen:'.$balance->getFrozen().'<br>'.PHP_EOL;
    }
});

Route::get('test-omid-withdraw', function () {

    $asset = \App\Services\Exchanges\Asset\AssetFactory::make('coinex');
    $res = $asset->withdraw(
        resolve(\App\Services\Exchanges\Asset\DTO\WithdrawRequestDTO::class)
        ->setCurrency('DOGE')
//        ->setChain('TRX')
        ->setAmount("1")
        ->setWithdrawMethod(\App\Services\Exchanges\Asset\Enum\WithdrawMethodEnum::INTER_USER)
        ->setAddress("o.shabani@hotmail.com")
    );
    dd($res);
});
