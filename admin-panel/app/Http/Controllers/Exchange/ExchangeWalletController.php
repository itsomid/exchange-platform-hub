<?php

namespace App\Http\Controllers\Exchange;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Services\NodeProviders\CryptoAPIService;
use App\Services\Exchanges\Asset\Coinex\Authentication\MethodEnum;
use App\Services\Exchanges\Asset\Coinex\CoinexRequest;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class ExchangeWalletController extends Controller
{
    protected $walletService;

    protected $cryptoApi;
    public function __construct(WalletService $walletService, CryptoAPIService $cryptoApi)
    {
        $this->walletService = $walletService;
        $this->cryptoApi = $cryptoApi;
    }
    public function coinexWallets()
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

//        $coinexAssets = array_map(function ($item) {
//            return resolve(BalanceResponseDTO::class)
//                ->setCcy($item['ccy'] ?? 'N/A')
//                ->setFrozen($item['frozen'] ?? '0')
//                ->setAvailable($item['available'] ?? '0');
//        }, $data);

         // Convert the array to an array of objects
//        $coinexAssets = json_decode(json_encode($data), false);

        // Convert the array to a collection of objects
        $coinexAssets = collect($data)->map(function ($item) {
            $asset = (object) $item;
            // Fetch the currency logo using the Currency model
            $currency = Currency::where('symbol', $asset->ccy)->first();

            // Add the coinLogo property to the asset object
            $asset->coinLogo = $currency ? $currency->coinLogo() : 'default-logo.png'; // Provide a default logo if not found

            return $asset;
        });


        return view('dashboard.exchange.wallet.exchange-coinex-wallets', [
            'coinexAssets' => $coinexAssets,
        ]);

    }

    public function localWallets()
    {
        $exchangeWallets = $this->walletService->getExchangeAllWallet();
        return view('dashboard.exchange.wallet.exchange-local-wallets', [
            'exchangeWallets' => $exchangeWallets,
        ]);

    }

    public function hotWallets()
    {
        $exchangeWalletChains = $this->walletService->getExchangeAllWalletChainExceptUSDT();


        $exchangeHotWallets =  $exchangeWalletChains->map(function ($walletChain) {
            $chainConfig = $this->cryptoApi->getCoinConfig($walletChain->wallet->currency_symbol);

            if (!$chainConfig) {
                return $walletChain;
            }

            $balance = $this->cryptoApi->getBalance(
                currency_symbol: $walletChain->wallet->currency_symbol,
                address: $walletChain->address
            );


            return $this->cryptoApi->enrichWalletData($walletChain, $balance);
        });

        return view('dashboard.exchange.wallet.exchange-hot-wallets', [
            'exchangeHotWallets' => $exchangeHotWallets,
        ]);

    }

}
