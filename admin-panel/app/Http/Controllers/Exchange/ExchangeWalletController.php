<?php

namespace App\Http\Controllers\Exchange;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Wallet;
use App\Models\WalletChain;
use App\Services\NodeProviders\BlockchairService;
use App\Services\NodeProviders\BscScanService;
use App\Services\NodeProviders\CryptoAPIService;
use App\Services\NodeProviders\EtherScanService;
use App\Services\NodeProviders\TronScanService;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ExchangeWalletController extends Controller
{
    protected $walletService;

    protected $cryptoApi;
    protected $blockchair;
    protected $bscScan;
    protected $tronScan;
    protected $etherScan;

    public function __construct(
        WalletService     $walletService,
        CryptoAPIService  $cryptoApi,
        BlockchairService $blockchair,
        BscScanService    $bscScan,
        TronScanService   $tronScan,
        EtherScanService  $etherScan,

    )
    {
        $this->walletService = $walletService;
        $this->cryptoApi = $cryptoApi;
        $this->blockchair = $blockchair;
        $this->bscScan = $bscScan;
        $this->tronScan = $tronScan;
        $this->etherScan = $etherScan;

        // Fetch and cache the balance for one hour
        $this->cacheBalances();
    }

    /**
     * Helper method to fetch balance data based on currency and chain
     *
     * @param string $currency
     * @param string $chain
     * @param string $address
     * @return array
     */
    protected function fetchBalanceData(string $currency, string $chain, string $address)
    {
        if($chain === 'ERC20'){
            return $this->etherScan->getBalance($currency, $address);
        }elseif($chain === 'TRC20'){
            return $this->tronScan->getBalance($currency, $address);
        }elseif($chain === 'BSC'){
            return $this->bscScan->getBalance($currency, $address);
        }elseif ($chain === 'DOGE' || $chain === 'BTC') {
            return $this->blockchair->getBalance($currency, $address);
        }else {
            return $this->cryptoApi->getBalance($currency, $address);
        }

        return ['error' => 'Unsupported currency or chain'];
    }

    protected function cacheBalances()
    {
        // Check if the balances are already cached
        $cachedBalances = Cache::get('wallet_balances', []);

        if (!empty($cachedBalances)) {
            return;
        }

        $walletChains = $this->walletService->getExchangeAllWalletChain();
        $balances = [];

        foreach ($walletChains as $walletChain) {
            $chain = $walletChain->currency_chain;
            $currency = $walletChain->wallet->currency_symbol;
            $address = $walletChain->address;

            $balanceData = $this->fetchBalanceData($currency, $chain, $address);

            if (!isset($balanceData['error'])) {
                $balances[$currency][$chain] = $balanceData['amount'];
            }
        }

        // Cache the balances for one hour
        Cache::put('wallet_balances', $balances, 3600);
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

        $exchangeWalletChains = $this->walletService->getExchangeAllWalletChain();
        return $exchangeWalletChains;
        $balances = Cache::get('wallet_balances', []);
        $formattedBalances = [];

        foreach ($exchangeWalletChains as $walletChain) {
            $currency = $walletChain->wallet->currency_symbol;
            $chain = $walletChain->currency_chain;

            // Check if the currency exists in the balances array
            if (isset($balances[$currency]) && isset($balances[$currency][$chain])) {
                $formattedBalances[$currency][$chain] = $balances[$currency][$chain];
            } else {
                // Handle the case where the currency or chain does not exist
                $formattedBalances[$currency][$chain] = '0'; // or any default value you prefer
            }
        }

        return view('dashboard.exchange.wallet.exchange-hot-wallets', [
            'exchangeWalletChains' => $exchangeWalletChains,
            'balances' => $formattedBalances,
        ]);

    }

    public function refreshHotWalletBalance(Request $request)
    {
        // Get the wallet chain ID from the request
        $walletChainId = $request->input('wallet_chain_id');

        // Fetch the wallet chain from the database
        $walletChain = WalletChain::findOrFail($walletChainId);

        // Extract currency symbol, address, and assume network is 'mainnet'
        $chain = $walletChain->currency_chain;
        $currency = $walletChain->wallet->currency_symbol;
        $address = $walletChain->address;

        // Fetch the latest balance
        $balanceData = $this->fetchBalanceData($currency, $chain, $address);

        // Check for errors in the API response
        if (isset($balanceData['error'])) {
            return response()->json(['error' => $balanceData['error']], 500);
        }

        // Update the cache with the new balance
        $balances = Cache::get('wallet_balances', []);

        // Check if the currency exists in the balances array
        if (!isset($balances[$currency])) {
            $balances[$currency] = [];
        }

        // Update the balance for the specific chain

        $balances[$currency][$chain] = formatNumberTrimZeros($balanceData['amount']);
//        return $balances;
        Cache::put('wallet_balances', $balances, 3600);

        // Return the balance data as JSON
        return response()->json([
            'amount' => formatNumberTrimZeros($balanceData['amount'])
        ]);
    }

    public function assetsGatheringToColdWallet(Request $request)
    {
        if ($request->has('currency_symbol')) {
            $currency_symbol = $request->currency_symbol;
        } else {
            $currency_symbol = 'USDT';
        }
        $currency = Currency::whereSymbol($currency_symbol)->first();

        $currencyChains = $currency->chains;
        $wallet = Wallet::where('currency_symbol', $currency->symbol)->first();


        $walletChains = $wallet->walletChains;

        return view('dashboard.exchange.wallet.assets-gathering-to-cold-wallet-form', [
            'currency' => $currency,
            'currencyChains' => $currencyChains,
            'walletChains' => $walletChains,
        ]);
        $chainName = $request->input('chain_name');
        $walletChain = WalletChain::where('currency_chain', $chainName)->first();

        $coldWalletAddress = $request->input('cold_wallet_address');


    }

}
