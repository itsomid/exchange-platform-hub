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

class WalletService
{
    protected $exchangeUserId;

    public function __construct()
    {
        // Load exchange user ID from config
        $this->exchangeUserId = config('exchange.exchange_user_id', 1);
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
            ->where('currency', $currency)
            ->first();
    }

    /**
     * Get the exchange (system) wallet for a specific currency.
     */
    public function getExchangeWallet(string $currency): ?Wallet
    {
        return Wallet::where('user_id', $this->exchangeUserId)
            ->where('currency_symbol', $currency)
            ->first();
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

    public function specificAssetValue(User $user, string $currency_symbol)
    {
        // Initialize the specific asset value
        $specificAssetValue = 0;

        // Loop through the user's wallets
        foreach ($user->wallets as $wallet) {
            // Check if the wallet's currency matches the provided currency ID
            if ($wallet->currency_symbol === $currency_symbol) {
                // Get the current market price for the wallet's currency
                $market = $wallet->currency->baseMarket; // Assuming you have a relationship in the Currency model

                $currencyPrice = $market ? $market->activeExchangePrice->price : 1;

                // Add the wallet's value to the specific asset value
                $specificAssetValue += $wallet->balance * $currencyPrice;
            }
        }

        return $specificAssetValue;
    }

    public function totalTransactionValueBasedType(string $currencySymbol, array $transactionTypes): float
    {
        // Get the total amount of deposits for the given currency
        $totalDeposits = Transaction::whereIn('type', $transactionTypes)
            ->whereHas('wallet', function ($query) use ($currencySymbol) {
                $query->where('currency_symbol', $currencySymbol);
            })
            ->sum('amount');

        // Fetch the exchange rate for the currency
        $currency = Currency::where('symbol', $currencySymbol)->first();
        $exchangeRate = $currency && $currency->baseMarket
            ? $currency->baseMarket->activeExchangePrice->price
            : 1; // Default to 1 if no exchange rate found

        // Calculate the value in USDT
        return $totalDeposits * $exchangeRate;
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

            Deposit::create([
                'user_id' => $userId,
                'currency_symbol' => $currencySymbol,
                'currency_chain' => $currencyChain,
                'amount' => 0,
                'address' => null,
                'transaction_hash' => null,
                'description' => $description ?? 'Awaiting deposit',
                'status' => 'pending',
            ]);


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
}
