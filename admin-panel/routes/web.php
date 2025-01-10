<?php

use App\Models\GatewayTransaction;
use App\Models\ReferralCode;
use App\Services\OrderService;
use App\Services\PaymentGateway\Exception\InvalidRequestException;
use App\Services\PaymentGateway\Exception\NotFoundTransactionException;
use App\Services\PaymentGateway\Exception\RetryException;
use App\Services\PaymentGateway\Gateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

//Route::view('/', 'welcome');

Route::redirect('', '/admin/login');
Route::get('/test', function (){
    return $user_id =  ReferralCode::query()
        ->inRandomOrder()
        ->value('user_id');
    return \App\Models\User::query()->find($user_id);
});

Route::get("test-omid", function (){
   $asset = \App\Services\Exchanges\Asset\AssetFactory::make("coinex");

   foreach ($asset->getBalance() as $balance){
       echo "currency:".$balance->getCcy().' available:'.$balance->getAvailable() . ' frozen:'.$balance->getFrozen();
   }
});
