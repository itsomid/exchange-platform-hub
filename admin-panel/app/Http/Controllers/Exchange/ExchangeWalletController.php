<?php

namespace App\Http\Controllers\Exchange;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletChain;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Services\NodeProviders\BlockchairService;
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
    protected WalletRepositoryInterface $walletRepository;

    protected $cryptoApi;
    protected $blockchair;
    protected $tronScan;
    protected $etherScan;
    protected $bitexroomUserId;

    public function __construct(
        WalletService $walletService,
        WalletRepositoryInterface $walletRepository,
        CryptoAPIService  $cryptoApi,
        BlockchairService $blockchair,
        TronScanService   $tronScan,
        EtherScanService  $etherScan,
    ) {
        $this->walletService = $walletService;
        $this->walletRepository = $walletRepository;
        $this->cryptoApi = $cryptoApi;
        $this->blockchair = $blockchair;
        $this->tronScan = $tronScan;
        $this->etherScan = $etherScan;
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

        if ($normalizedChain === 'TRC20') {
            return $this->tronScan->getBalance($currency, $address);
        }

        if (in_array($normalizedChain, ['BTC', 'DOGE', 'LTC', 'DASH'])) {
            return $this->blockchair->getBalance($currency, $address);
        }

        $chainId = match ($normalizedChain) {
            'ERC20'     => 1,
            'BSC'       => 56,
            'POLYGON'   => 137,
            'ARBITRUM'  => 42161,
            'OPTIMISM'  => 10,
            'AVALANCHE' => 43114,
            'SONIC'     => 146,
            default     => null,
        };

        if ($chainId !== null) {
            return $this->etherScan->getBalance($currency, $address, $chainId);
        }

        return $this->cryptoApi->getBalance($currency, $address, 'mainnet', null);

    }

    protected function cacheBalances(?WalletChain $walletChain = null, bool $forceRefresh = false): array
    {
        $balances = Cache::get(self::HOT_WALLET_BALANCES_CACHE_KEY, []);

        // If no target wallet chain is provided, just return current cache snapshot.
        if (!$walletChain) {
            return [
                'balances' => $balances,
            ];
        }

        $walletChain->loadMissing('wallet');

        $chain = $walletChain->currency_chain;
        $currency = $walletChain->wallet->currency_symbol;

        if (!$forceRefresh && isset($balances[$currency]) && array_key_exists($chain, $balances[$currency])) {
            return [
                'balances' => $balances,
                'amount' => $balances[$currency][$chain],
                'from_cache' => true,
            ];
        }

        $balanceData = $this->fetchBalanceData($currency, $chain, $walletChain->address);
        if (isset($balanceData['error'])) {
            return [
                'balances' => $balances,
                'error' => $balanceData['error'],
            ];
        }

        $formattedAmount = formatNumberTrimZeros($balanceData['amount'] ?? 0);

        if (!isset($balances[$currency])) {
            $balances[$currency] = [];
        }

        $balances[$currency][$chain] = $formattedAmount;
        Cache::put(self::HOT_WALLET_BALANCES_CACHE_KEY, $balances, 3600);

        return [
            'balances' => $balances,
            'amount' => $formattedAmount,
            'from_cache' => false,
        ];
    }


    public function localWallets()
    {
        $exchangeWallets = $this->walletRepository->getBitexroomAllWallets()->load('currency');

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

        $exchangeWalletChains = $this->walletRepository->getBitexroomAllWalletChains();
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

        return view('dashboard.exchange.wallet.exchange-hot-wallets', [
            'exchangeWalletChains' => $exchangeWalletChains,
            'chainLogoMap' => $chainLogoMap,
        ]);
    }

    public function hotWalletBalance(Request $request, WalletChain $walletChain)
    {
        $walletChain->loadMissing('wallet');

        $chain = $walletChain->currency_chain;
        $currency = $walletChain->wallet->currency_symbol;
        $forceRefresh = $request->boolean('force_refresh');
        $cacheResult = $this->cacheBalances($walletChain, $forceRefresh);

        if (isset($cacheResult['error'])) {
            return response()->json(['error' => $cacheResult['error']], 500);
        }

        $formattedAmount = $cacheResult['amount'] ?? '0';

        return response()->json([
            'wallet_chain_id' => $walletChain->id,
            'currency' => $currency,
            'chain' => $chain,
            'amount' => $formattedAmount,
            'status' => !empty($cacheResult['from_cache']) ? 'cached' : 'updated',
        ]);
    }

    public function refreshHotWalletBalance(Request $request)
    {
        // Get the wallet chain ID from the request
        $walletChainId = $request->input('wallet_chain_id');

        // Fetch the wallet chain from the database
        $walletChain = WalletChain::findOrFail($walletChainId);

        $walletChain->loadMissing('wallet');

        // Force a fresh fetch on manual refresh by clearing this row from cache first.
        $chain = $walletChain->currency_chain;
        $currency = $walletChain->wallet->currency_symbol;
        $balances = Cache::get(self::HOT_WALLET_BALANCES_CACHE_KEY, []);
        if (isset($balances[$currency]) && array_key_exists($chain, $balances[$currency])) {
            unset($balances[$currency][$chain]);
            if (empty($balances[$currency])) {
                unset($balances[$currency]);
            }
            Cache::put(self::HOT_WALLET_BALANCES_CACHE_KEY, $balances, 3600);
        }

        $cacheResult = $this->cacheBalances($walletChain);
        if (isset($cacheResult['error'])) {
            return response()->json(['error' => $cacheResult['error']], 500);
        }

        // Return the balance data as JSON
        return response()->json([
            'amount' => $cacheResult['amount'] ?? '0',
            'status' => !empty($cacheResult['from_cache']) ? 'cached' : 'updated',
        ]);
    }

}
