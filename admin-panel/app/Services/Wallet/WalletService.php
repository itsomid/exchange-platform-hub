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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Models\MarketHistory;

class WalletService
{
    protected $bitexroomUserId;

    public function __construct()
    {
        // Load exchange user ID from config
        $this->bitexroomUserId = config('bitexroom.user_id', 1);
    }


    /**
     * Calculate the total assets value for a user's wallets.
     *
     * @param \App\Models\User $user
     * @return float
     */
    /**
     * Get a user's wallet by currency.
     */
    public function getUserWallet(int $userId, string $currency): ?Wallet
    {
        return Wallet::where('user_id', $userId)
            ->where('currency_symbol', $currency)
            ->first();
    }

    /**
     * Get the exchange (system) wallet for a specific currency.
     */
    public function getExchangeWallet(string $currency): ?Wallet
    {
        return Wallet::where('user_id', $this->bitexroomUserId)
            ->where('currency_symbol', $currency)
            ->first();
    }

    public function getExchangeAllWallet()
    {
        return Wallet::where('user_id', $this->bitexroomUserId)->get();
    }
    public function getExchangeAllWalletExceptUSDT()
    {
        return Wallet::where('user_id', $this->bitexroomUserId)->where('currency_symbol', '!=', 'USDT')->get();
    }

    public function getExchangeAllWalletChain()
    {
        $allExchangeWallet = $this->getExchangeAllWallet();
        $walletIds = $allExchangeWallet->pluck('id')->toArray();
        return WalletChain::with('wallet')->whereIn('wallet_id', $walletIds)->get();
    }
    public function getExchangeAllWalletChainExceptUSDT()
    {
        $allExchangeWallet = $this->getExchangeAllWalletExceptUSDT();
        $walletIds = $allExchangeWallet->pluck('id')->toArray();
        return WalletChain::with(['wallet', 'wallet.currency'])->whereIn('wallet_id', $walletIds)->get();
    }
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

            // Skip if no market (like USDT)
            if (!$market) {
                Log::warning('Currency has no base market', [
                    'currency_symbol' => $currency->symbol,
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                ]);
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
                $existingWallet = $this->getExchangeWallet($currencySymbol);
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
                $existingWallet = $this->getExchangeWallet($currency->symbol);

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
            $exchangeWallets = Wallet::where('user_id', $this->bitexroomUserId)
                ->with(['currency.chains', 'walletChains'])
                ->get();

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
}
