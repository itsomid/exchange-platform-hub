<?php

namespace App\Http\Controllers\Exchange;

use App\Http\Controllers\Controller;
use App\Services\Exchanges\Asset\Coinex\Authentication\MethodEnum;
use App\Services\Exchanges\Asset\Coinex\CoinexRequest;
use App\Services\Exchanges\Asset\DTO\BalanceResponseDTO;
use Illuminate\Http\Request;

class ExchangeWalletController extends Controller
{
    public function index()
    {
        $response = CoinexRequest::send(MethodEnum::GET, "/v2/assets/spot/balance");
        if ($response->json('code') !== 0) {
            \Log::error('API Error:', $response->json());
            return [];
        }

        $data = $response->json('data');
        if (!is_array($data) || empty($data)) {
            return [];
        }
        $coinexAssets = array_map(function ($item) {
            return resolve(BalanceResponseDTO::class)
                ->setCcy($item['ccy'] ?? 'N/A')
                ->setFrozen($item['frozen'] ?? '0')
                ->setAvailable($item['available'] ?? '0');
        }, $data);


        return view('dashboard.exchange.wallet.exchange-wallets', [
            'coinexAssets' => $coinexAssets,
        ]);

    }
}
