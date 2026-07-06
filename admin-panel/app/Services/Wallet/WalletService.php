<?php

namespace App\Services\Wallet;

use App\Enums\BalanceOperationEnum;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletChain;
use App\Models\CurrencyChain;
use App\Services\Wallet\DTO\UpdateBalanceRequestDTO;
use App\Infrastructure\HDWalletNew\HDWalletFacade;
use App\Exceptions\V1\Wallet\InternalWalletHasProblemException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Models\MarketHistory;
use App\Repositories\WalletRepository;
use App\Repositories\WalletChainRepository;

class WalletService
{
    protected $bitexroomUserId;

    public function __construct(
        private readonly WalletRepository $walletRepository,
        private readonly WalletChainRepository $walletChainRepository
    ) {
        // Load exchange user ID from config
        $this->bitexroomUserId = config('bitexroom.user_id', 1);
    }


    /**
     * Calculate the total assets value for a user's wallets.
     */
    public function totalAssetsValue(User $user)
    {

        $totalAssetsValue = 0;

        $user->loadMissing('wallets.currency.baseMarket.activeExchangePrice');

        foreach ($user->wallets as $wallet) {
            $currency = $wallet->currency;
            if (!$currency) {
                Log::warning('Wallet currency missing', [
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'currency_symbol' => $wallet->currency_symbol,
                ]);
                $currencyPrice = 1;
            } else {
                $currencyPrice = $currency->exchangePrice;
            }

            $totalAssetsValue += $wallet->balance * $currencyPrice;
        }

        return $totalAssetsValue;
    }

    public function totalAvailableAssetsValue(User $user)
    {
        // Initialize the total assets value
        $totalAssetsValue = 0;

        $user->loadMissing('wallets.currency.baseMarket.activeExchangePrice');

        // Loop through each wallet and calculate its value
        foreach ($user->wallets as $wallet) {
            $currency = $wallet->currency;
            if (!$currency) {
                Log::warning('Wallet currency missing', [
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'currency_symbol' => $wallet->currency_symbol,
                ]);
                $currencyPrice = 1;
            } else {
                $currencyPrice = $currency->exchangePrice;
            }

            $totalAssetsValue += ($wallet->balance - $wallet->locked_balance) * $currencyPrice;
        }

        return $totalAssetsValue;
    }

    public function totalBlockedAssetsValue(User $user)
    {
        // Initialize the total assets value
        $totalAssetsValue = 0;

        $user->loadMissing('wallets.currency.baseMarket.activeExchangePrice');

        // Loop through each wallet and calculate its value
        foreach ($user->wallets as $wallet) {
            $currency = $wallet->currency;
            if (!$currency) {
                Log::warning('Wallet currency missing', [
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'currency_symbol' => $wallet->currency_symbol,
                ]);
                $currencyPrice = 1;
            } else {
                $currencyPrice = $currency->exchangePrice;
            }

            $totalAssetsValue += $wallet->locked_balance * $currencyPrice;
        }

        return $totalAssetsValue;
    }

    public function specificAssetValue(User $user, string $currencySymbol)
    {
        $wallet = Wallet::where('user_id', $user->id)
            ->where('currency_symbol', $currencySymbol)
            ->first();

        if (!$wallet) {
            return 0;
        }

        // Get the current market price for the wallet's currency
        $wallet->loadMissing('currency.baseMarket.activeExchangePrice');
        $currency = $wallet->currency;
        if (!$currency) {
            Log::warning('Wallet currency missing', [
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'currency_symbol' => $wallet->currency_symbol,
            ]);
            $currencyPrice = 1;
        } else {
            $currencyPrice = $currency->exchangePrice;
        }

        return $wallet->balance * $currencyPrice;
    }

    /**
     * Calculate user's profit/loss from yesterday.
     *
     * @param User $user
     * @return array containing the profit/loss value and percentage
     */
    public function calculateYesterdayProfitLoss(User $user)
    {
        // Get yesterday and the day before
        $yesterday = now()->subDay();
        $dayBefore = now()->subDays(2);

        // Initialize values
        $yesterdayValue = 0;
        $dayBeforeValue = 0;

        $user->loadMissing('wallets.currency.baseMarket');

        // Loop through each wallet
        foreach ($user->wallets as $wallet) {
            $currency = $wallet->currency;
            if (!$currency) {
                Log::warning('Wallet currency missing', [
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'currency_symbol' => $wallet->currency_symbol,
                ]);
                continue;
            }

            // Get the market for the wallet's currency
            $market = $currency->baseMarket;

            // Handle stablecoins like USDT that don't have base markets
            if (!$market) {
                // For stablecoins, use the exchange price (which defaults to 1 for USDT)
                $currencyPrice = $currency->exchangePrice;
                $yesterdayValue += $wallet->balance * $currencyPrice;
                $dayBeforeValue += $wallet->balance * $currencyPrice;
                continue;
            }

            // Get market history for yesterday
            $yesterdayHistory = MarketHistory::where('market_id', $market->id)
                ->whereDate('timestamp', $yesterday)
                ->latest()
                ->first();

            // Get market history for day before
            $dayBeforeHistory = MarketHistory::where('market_id', $market->id)
                ->whereDate('timestamp', $dayBefore)
                ->latest()
                ->first();

            // Add to totals if history exists
            if ($yesterdayHistory) {
                $yesterdayValue += $wallet->balance * $yesterdayHistory->close;
            }

            if ($dayBeforeHistory) {
                $dayBeforeValue += $wallet->balance * $dayBeforeHistory->close;
            }
        }

        // Calculate profit/loss
        $profitLoss = $yesterdayValue - $dayBeforeValue;

        // Calculate percentage
        $profitLossPercentage = $dayBeforeValue > 0
            ? ($profitLoss / $dayBeforeValue) * 100
            : 0;

        return [
            'value' => $profitLoss,
            'percentage' => $profitLossPercentage
        ];
    }

    public function updateBalance(UpdateBalanceRequestDTO $requestDTO): bool
    {
        try {
            DB::transaction(function () use ($requestDTO) {
                $wallet = Wallet::query()
                    ->where('currency_symbol', $requestDTO->getCurrencySymbol())
                    ->where('user_id', $requestDTO->getUserId())
                    ->lockForUpdate()
                    ->firstOrFail(); // Ensures wallet exists, throws exception otherwise

                $newBalance = $this->calculateNewBalance(
                    $wallet->balance,
                    $requestDTO->getAmount(),
                    $requestDTO->getOperation()
                );

                $wallet->update(['balance' => $newBalance]);
            });

            return true;
        } catch (Throwable $exception) {
            report($exception);
            return false;
        }
    }

    private function calculateNewBalance(string $currentBalance, string $amount, BalanceOperationEnum $operation): string
    {
        return match ($operation) {
            BalanceOperationEnum::INCREASE => bcadd($currentBalance, $amount, 8),
            BalanceOperationEnum::DECREASE => bcsub($currentBalance, $amount, 8),
            default => throw new \InvalidArgumentException("Invalid balance operation: {$operation}"),
        };
    }

    public function createDepositAddress(int $userId, string $currencySymbol, string $currencyChain)
    {
        DB::beginTransaction();

        try {

            $wallet = Wallet::firstOrCreate(
                ['user_id' => $userId, 'currency_symbol' => $currencySymbol],
                ['balance' => 0, 'locked_balance' => 0]
            );

            // Check if a deposit address for this chain already exists
            $existingChain = WalletChain::where('wallet_id', $wallet->id)
                ->where('currency_chain', $currencyChain)
                ->first();

            if (!$existingChain) {

                //TODO: give it from HD Wallet
                //                $depositAddress = $this->generateUniqueAddress();

                // Create the wallet chain record
                $existingChain = WalletChain::create([
                    'wallet_id' => $wallet->id,
                    'currency_chain' => $currencyChain,
                    'address' => null,
                ]);
                //                throw new \Exception("A deposit address for this currency chain already exists.");
            } else {
                echo "A deposit address for this currency chain already exists.\n";
            }



            DB::commit();

            return $existingChain;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function generateUniqueAddress()
    {
        // Placeholder for address generation logic
        return '0x' . bin2hex(random_bytes(20));
    }

    /**
     * Check if user has sufficient balance.
     *
     * @param int $userId
     * @param string $currencySymbol
     * @param float $amount
     * @return bool True if balance is sufficient, false otherwise
     */
    public function checkBalance(int $userId, string $currencySymbol, float $amount): bool
    {
        try {
            return DB::transaction(function () use ($userId, $currencySymbol, $amount) {
                $wallet = Wallet::where('user_id', $userId)
                    ->where('currency_symbol', $currencySymbol)
                    ->lockForUpdate()
                    ->first();

                return $wallet && $wallet->balance >= $amount;
            });
        } catch (\Throwable $exception) {
            report($exception);
            return false;
        }
    }

    /**
     * Decrease user's balance by the specified amount.
     *
     * @param int $userId
     * @param string $currencySymbol
     * @param float $amount
     * @return bool True if balance was decremented successfully, false otherwise
     */
    public function decreaseBalance(int $userId, string $currencySymbol, float $amount): bool
    {
        try {
            return DB::transaction(function () use ($userId, $currencySymbol, $amount) {
                $wallet = Wallet::where('user_id', $userId)
                    ->where('currency_symbol', $currencySymbol)
                    ->lockForUpdate()
                    ->first();

                if (!$wallet || $wallet->balance < $amount) {
                    return false;
                }

                $wallet->decrement('balance', $amount);
                return true;
            });
        } catch (\Throwable $exception) {
            report($exception);
            return false;
        }
    }

    public function increaseBalance(int $userId, string $currencySymbol, float $amount): bool
    {
        return DB::transaction(function () use ($userId, $currencySymbol, $amount) {
            $wallet = Wallet::where('user_id', $userId)
                ->where('currency_symbol', $currencySymbol)
                ->lockForUpdate()
                ->first();
            if (!$wallet) {
                return false;
            }
            $wallet->increment('balance', $amount);
            return true;
        });
    }

    /**
     * Create exchange wallet for a specific currency if it doesn't exist.
     *
     * @param string $currencySymbol
     * @return Wallet|null
     */
    public function createExchangeWallet(string $currencySymbol): ?Wallet
    {
        try {
            return DB::transaction(function () use ($currencySymbol) {
                // Check if exchange wallet already exists
                $existingWallet = $this->walletRepository->getBitexroomWallet($currencySymbol);
                if ($existingWallet) {
                    return $existingWallet;
                }

                // Create new exchange wallet
                $wallet = Wallet::create([
                    'user_id' => $this->bitexroomUserId,
                    'currency_symbol' => $currencySymbol,
                    'balance' => 0,
                    'locked_balance' => 0,
                ]);

                return $wallet;
            });
        } catch (\Throwable $exception) {
            report($exception);
            return null;
        }
    }

    /**
     * Create exchange wallets for all currencies that don't have exchange wallets.
     *
     * @return array Array of created wallets
     */
    public function createMissingExchangeWallets(): array
    {
        $createdWallets = [];

        try {
            // Get all currencies
            $currencies = \App\Models\Currency::all();

            foreach ($currencies as $currency) {
                $existingWallet = $this->walletRepository->getBitexroomWallet($currency->symbol);

                if (!$existingWallet) {
                    $wallet = $this->createExchangeWallet($currency->symbol);
                    if ($wallet) {
                        $createdWallets[] = $wallet;
                    }
                }
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $createdWallets;
    }

    /**
     * Create missing wallet chains for all exchange wallets based on defined currency chains.
     *
     * @return array<int, array<string, mixed>> List of created chains with wallet and chain info
     */
    public function createMissingExchangeWalletChains(): array
    {
        $createdChains = [];
        try {
            $exchangeWallets = $this->walletRepository->getBitexroomAllWallets()
                ->load(['currency.chains', 'walletChains']);

            foreach ($exchangeWallets as $wallet) {
                if (!$wallet->currency) {
                    continue;
                }

                $existingChainNames = $wallet->walletChains->pluck('currency_chain')->toArray();
                foreach ($wallet->currency->chains as $currencyChain) {
                    $chainValue = $currencyChain->chain->value;
                    if (!in_array($chainValue, $existingChainNames, true)) {
                        $walletChain = WalletChain::create([
                            'wallet_id' => $wallet->id,
                            'currency_chain' => $chainValue,
                            'address' => null,
                        ]);
                        $createdChains[] = [
                            'wallet_id' => $wallet->id,
                            'currency_symbol' => $wallet->currency_symbol,
                            'currency_chain' => $walletChain->currency_chain,
                        ];
                    }
                }
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $createdChains;
    }

    /**
     * Create one wallet chain for the exchange wallet matching the provided currency chain.
     */
    public function createExchangeWalletChain(CurrencyChain $currencyChain): ?WalletChain
    {
        try {
            return DB::transaction(function () use ($currencyChain) {
                // Resolve currency symbol
                $currency = $currencyChain->currency ?: Currency::find($currencyChain->currency_id);
                if (!$currency) {
                    return null;
                }

                // Ensure exchange wallet exists
                $wallet = $this->createExchangeWallet($currency->symbol);
                if (!$wallet) {
                    return null;
                }

                // Chain value can be enum or string
                $chainValue = is_string($currencyChain->chain) ? $currencyChain->chain : $currencyChain->chain->value;

                // Skip if already exists
                $existing = WalletChain::where('wallet_id', $wallet->id)
                    ->where('currency_chain', $chainValue)
                    ->first();
                if ($existing) {
                    return $existing;
                }

                return WalletChain::create([
                    'wallet_id' => $wallet->id,
                    'currency_chain' => $chainValue,
                    'address' => null,
                ]);
            });
        } catch (\Throwable $exception) {
            report($exception);
            return null;
        }
    }

    /**
     * Generate address for user wallet
     * 
     * @param int $userId
     * @param string $currency
     * @param string $chain
     * @return string
     * @throws InternalWalletHasProblemException
     */
    public function generateAddress(int $userId, string $currency, string $chain): string
    {
        try {
            // Find the user
            $user = User::find($userId);
            $blockchainName = CurrencyChain::where('chain', $chain)
                ->first()->blockchain_name;
            if (!$user) {
                throw new InternalWalletHasProblemException('User not found');
            }

            // Find the currency
            $currencyModel = Currency::where('symbol', $currency)->first();
            if (!$currencyModel) {
                throw new InternalWalletHasProblemException('Currency not found');
            }

            // Find the currency chain
            $currencyChain = CurrencyChain::where('currency_id', $currencyModel->id)
                ->where('chain', $chain)
                ->first();
            if (!$currencyChain) {
                throw new InternalWalletHasProblemException('Currency chain not found');
            }

            // Find or create wallet
            $wallet = $this->walletRepository->getOrCreateWallet($userId, $currency);
            if (!$wallet) {
                throw new InternalWalletHasProblemException('Failed to create wallet');
            }
            // Find or create wallet chain
            $walletChain = $this->walletChainRepository->createOrGetChain($wallet->id, $currencyChain->chain->value);
            if (!$walletChain) {
                throw new InternalWalletHasProblemException('Failed to create wallet chain');
            }

            // If address already exists, return it
            if ($walletChain->address) {
                return $walletChain->address;
            }

            // Generate new address using HDWallet Facade
            $hdWalletFacade = resolve(HDWalletFacade::class);
            $address = $hdWalletFacade->generateAddress($userId, $blockchainName, $currency);

            // Update wallet chain with the new address
            $walletChain->update(['address' => $address]);

            return $address;
        } catch (InternalWalletHasProblemException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            throw new InternalWalletHasProblemException('Failed to generate address: ' . $exception->getMessage());
        }
    }

    /**
     * Create wallet chains for all available chains of a specific currency for a user
     *
     * @param int $userId
     * @param string $currencySymbol
     * @return array<string, mixed> Result with success status and created chains info
     */
    public function createWalletChainsForCurrency(int $userId, string $currencySymbol): array
    {
        try {
            return DB::transaction(function () use ($userId, $currencySymbol) {
                // Find the currency with its chains
                $currency = Currency::with('chains')->where('symbol', $currencySymbol)->first();
                if (!$currency) {
                    return [
                        'success' => false,
                        'message' => 'Currency not found',
                        'created_chains' => []
                    ];
                }

                // Get or create the wallet for this user and currency
                $wallet = $this->walletRepository->getOrCreateWallet($userId, $currencySymbol);
                if (!$wallet) {
                    return [
                        'success' => false,
                        'message' => 'Failed to create or get wallet',
                        'created_chains' => []
                    ];
                }

                $createdChains = [];
                $existingChains = $wallet->walletChains->pluck('currency_chain')->toArray();

                // Create wallet chains for all currency chains that don't exist yet
                foreach ($currency->chains as $currencyChain) {
                    $chainValue = is_string($currencyChain->chain) ? $currencyChain->chain : $currencyChain->chain->value;

                    if (!in_array($chainValue, $existingChains, true)) {
                        $walletChain = $this->walletChainRepository->createOrGetChain($wallet->id, $chainValue);
                        if ($walletChain) {
                            $createdChains[] = [
                                'chain' => $chainValue,
                                'wallet_chain_id' => $walletChain->id,
                                'currency_symbol' => $currencySymbol
                            ];
                        }
                    }
                }

                return [
                    'success' => true,
                    'message' => count($createdChains) > 0 ? 'Wallet chains created successfully' : 'All wallet chains already exist',
                    'created_chains' => $createdChains,
                    'wallet_id' => $wallet->id
                ];
            });
        } catch (\Throwable $exception) {
            report($exception);
            return [
                'success' => false,
                'message' => 'An error occurred while creating wallet chains: ' . $exception->getMessage(),
                'created_chains' => []
            ];
        }
    }
}
