<?php

namespace App\Http\Controllers\Exchange;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Models\User;
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
    private const HOT_WALLET_BALANCES_CACHE_KEY = 'wallet_balances_v2';

    protected $walletService;

    protected $cryptoApi;
    protected $blockchair;
    protected $bscScan;
    protected $tronScan;
    protected $etherScan;
    protected $bitexroomUserId;

    public function __construct(
        WalletService     $walletService,
        CryptoAPIService  $cryptoApi,
        BlockchairService $blockchair,
        BscScanService    $bscScan,
        TronScanService   $tronScan,
        EtherScanService  $etherScan,

    ) {
        $this->walletService = $walletService;
        $this->cryptoApi = $cryptoApi;
        $this->blockchair = $blockchair;
        $this->bscScan = $bscScan;
        $this->tronScan = $tronScan;
        $this->etherScan = $etherScan;

        // Fetch and cache the balance for one hour
        $this->cacheBalances();
        $this->bitexroomUserId = config('bitexroom.user_id', 1);
    }

    /**
     * Helper method to fetch balance data based on currency and chain
     *
     * @param string $currency
     * @param string $chain
     * @param string|null $address
     * @return array
     */
    protected function fetchBalanceData(string $currency, string $chain, ?string $address)
    {
        // If address is not yet available, avoid calling providers and return zero balance
        if (empty($address)) {
            return ['amount' => '0'];
        }
        $normalizedChain = strtoupper($chain);

        if ($normalizedChain === 'ERC20') {
            return $this->etherScan->getBalance($currency, $address);
        } elseif ($normalizedChain === 'TRC20') {
            return $this->tronScan->getBalance($currency, $address);
        } elseif ($normalizedChain === 'BSC') {
            return $this->bscScan->getBalance($currency, $address);
        } elseif ($normalizedChain === 'DOGE' || $normalizedChain === 'BTC') {
            return $this->blockchair->getBalance($currency, $address);
        }

        $cryptoApiChain = match ($normalizedChain) {
            'OPTIMISM' => 'optimism',
            'ARBITRUM' => 'arbitrum',
            'POLYGON' => 'polygon',
            'AVALANCHE' => 'avalanche',
            'SONIC' => 'sonic',
            default => null,
        };

        return $this->cryptoApi->getBalance($currency, $address, 'mainnet', $cryptoApiChain);

    }

    protected function cacheBalances()
    {
        // Check if the balances are already cached
        $cachedBalances = Cache::get(self::HOT_WALLET_BALANCES_CACHE_KEY, []);

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
        Cache::put(self::HOT_WALLET_BALANCES_CACHE_KEY, $balances, 3600);
    }


    public function localWallets()
    {
        $exchangeWallets = $this->walletService->getExchangeAllWallet()->load('currency');

        $exchangeUser = User::find($this->bitexroomUserId);

        // Match user wallets behavior: compute and attach per-wallet asset value.
        $exchangeWallets = $exchangeWallets->map(function ($wallet) use ($exchangeUser) {
            $wallet->assetValue = $exchangeUser
                ? $this->walletService->specificAssetValue($exchangeUser, $wallet->currency_symbol)
                : 0;

            return $wallet;
        })->values();

        $symbols = $exchangeWallets
            ->pluck('currency_symbol')
            ->filter()
            ->map(fn($symbol) => strtoupper((string) $symbol))
            ->unique()
            ->values();

        $currenciesBySymbol = Currency::whereIn('symbol', $symbols)
            ->get()
            ->keyBy(fn($currency) => strtoupper((string) $currency->symbol));

        $currencyIds = $currenciesBySymbol->pluck('id')->values();

        $baseChainsByCurrencyId = CurrencyChain::with('currency')
            ->whereIn('currency_id', $currencyIds)
            ->where('is_base_coin', true)
            ->get()
            ->groupBy('currency_id')
            ->map(fn($group) => $group->first());



        $walletRows = $exchangeWallets
            ->map(function ($wallet) use ($currenciesBySymbol, $baseChainsByCurrencyId) {
                $symbol = strtoupper((string) ($wallet->currency_symbol ?? ''));
                $currency = $currenciesBySymbol->get($symbol);
                $baseChain = $currency ? $baseChainsByCurrencyId->get($currency->id) : null;
                $chainKey = strtoupper((string) ($baseChain?->chain?->value ?? $baseChain?->chain ?? 'OTHER'));

                return [
                    'wallet' => $wallet,
                    'symbol' => $symbol,
                    'chain' => $chainKey,
                ];
            })
            ->sortByDesc(function ($row) {
                return (float) ($row['wallet']->balance ?? 0);
            })
            ->values();

        return view('dashboard.exchange.wallet.exchange-local-wallets', [
            'exchangeWallets' => $exchangeWallets,
            'walletRows' => $walletRows,
        ]);
    }


    public function hotWallets()
    {

        $exchangeWalletChains = $this->walletService->getExchangeAllWalletChain();
        $chains = $exchangeWalletChains
            ->pluck('currency_chain')
            ->filter()
            ->map(fn($chain) => strtoupper((string) $chain))
            ->unique()
            ->values();

        $chainLogoMap = CurrencyChain::with('currency')
            ->whereIn('chain', $chains)
            ->where('is_base_coin', true)
            ->get()
            ->mapWithKeys(function ($currencyChain) {
                $chainKey = strtoupper($currencyChain->chain?->value ?? (string) $currencyChain->chain);

                return [$chainKey => $currencyChain->currency?->coinLogo()];
            })
            ->filter()
            ->all();

        $balances = Cache::get(self::HOT_WALLET_BALANCES_CACHE_KEY, []);
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
            'chainLogoMap' => $chainLogoMap,
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
        $balances = Cache::get(self::HOT_WALLET_BALANCES_CACHE_KEY, []);

        // Check if the currency exists in the balances array
        if (!isset($balances[$currency])) {
            $balances[$currency] = [];
        }

        // Update the balance for the specific chain

        $balances[$currency][$chain] = formatNumberTrimZeros($balanceData['amount']);
        //        return $balances;
        Cache::put(self::HOT_WALLET_BALANCES_CACHE_KEY, $balances, 3600);

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
        $wallet = Wallet::where('user_id',$this->bitexroomUserId)->where('currency_symbol', $currency->symbol)->first();

        $walletChains = $wallet->walletChains;

        return view('dashboard.exchange.wallet.assets-gathering-to-cold-wallet-form', [
            'currency' => $currency,
            'currencyChains' => $currencyChains,
            'walletChains' => $walletChains,
        ]);
    }
}
