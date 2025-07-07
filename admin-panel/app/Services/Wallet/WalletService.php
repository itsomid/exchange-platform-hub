<?php

namespace App\Services\Wallet;

use App\Enums\BalanceOperationEnum;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletChain;
use App\Services\Wallet\DTO\UpdateBalanceRequestDTO;
use Illuminate\Support\Facades\DB;
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
        return Wallet::where('user_id', $this->bitexroomUserId)->where('currency_symbol','!=','USDT')->get();
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
        return WalletChain::with(['wallet','wallet.currency'])->whereIn('wallet_id', $walletIds)->get();

    }
    public function totalAssetsValue(User $user)
    {
        // Initialize the total assets value
        $totalAssetsValue = 0;

        // Loop through each wallet and calculate its value
        foreach ($user->wallets as $wallet) {
            // Get the current market price for the wallet's currency
            $market = $wallet->currency->baseMarket; // Assuming you have a relationship in the Currency model

            $currencyPrice = $market ? $market->activeExchangePrice->price : 1;

            $totalAssetsValue += $wallet->balance * $currencyPrice;
//
        }

        return $totalAssetsValue;
    }

    public function totalAvailableAssetsValue(User $user)
    {
        // Initialize the total assets value
        $totalAssetsValue = 0;

        // Loop through each wallet and calculate its value
        foreach ($user->wallets as $wallet) {
            // Get the current market price for the wallet's currency
            $market = $wallet->currency->baseMarket; // Assuming you have a relationship in the Currency model

            $currencyPrice = $market ? $market->activeExchangePrice->price : 1;

            $totalAssetsValue += ($wallet->balance - $wallet->locked_balance) * $currencyPrice;
//
        }

        return $totalAssetsValue;
    }

    public function totalBlockedAssetsValue(User $user)
    {
        // Initialize the total assets value
        $totalAssetsValue = 0;

        // Loop through each wallet and calculate its value
        foreach ($user->wallets as $wallet) {
            // Get the current market price for the wallet's currency
            $market = $wallet->currency->baseMarket; // Assuming you have a relationship in the Currency model

            $currencyPrice = $market ? $market->activeExchangePrice->price : 1;

            $totalAssetsValue += $wallet->locked_balance * $currencyPrice;
//
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
        $market = $wallet->currency->baseMarket;
        $currencyPrice = $market ? $market->activeExchangePrice->price : 1;

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

        // Loop through each wallet
        foreach ($user->wallets as $wallet) {
            // Get the market for the wallet's currency
            $market = $wallet->currency->baseMarket;

            // Skip if no market (like USDT)
            if (!$market) {
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
}
