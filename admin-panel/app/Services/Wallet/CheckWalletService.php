<?php

namespace App\Services\Wallet;

use App\Enums\DepositStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Events\DepositDetected;
use App\Exceptions\V1\Wallet\InternalWalletHasProblemException;
use App\Functions\FlashMessages\Toast;
use App\Helpers\Math;
use App\Infrastructure\HDWallet\DTO\HDDeposit\GetDepositListsRequestDTO;
use App\Infrastructure\HDWalletNew\HDWalletFacade;
use App\Models\Currency;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallet\DTO\CheckWallet\CheckUserDepositRequestDTO;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckWalletService
{
    public function __construct() {}

    /**
     * @throws InternalWalletHasProblemException
     */
    public function checkUserDeposit(CheckUserDepositRequestDTO $requestDTO): bool
    {

        $hasNewTransaction = false;
        $transactionCount = 0;
        $totalDepositAmount = 0;
        $user = User::find($requestDTO->getUserId());
        $wallet = Wallet::where('user_id', $requestDTO->getUserId())->where('currency_symbol', $requestDTO->getCurrencySymbol())->first();

        $wallet = $wallet->load('chains.wallet.currency.chains');

        $walletChains = $wallet->chains;

        if (! $walletChains->contains(fn($chain) => ! empty($chain->address))) {
            Toast::message('کاربر آدرس زنجیره‌ای ندارد.')->warning()->notify();
            return false;
        }

        try {
            $hdDeposit = resolve(HDWalletFacade::class);
        } catch (\Throwable $e) {
            report($e);
            throw new InternalWalletHasProblemException('سرویس HD Wallet در دسترس نیست.');
        }

        foreach ($walletChains as $walletChain) {
            if (empty($walletChain->address)) {
                continue;
            }
            $currencyChain = $walletChain->wallet->currency->chains->where('chain', $walletChain->currency_chain)->first();

            try {
                $transactions = $hdDeposit->getDepositLists(
                    resolve(GetDepositListsRequestDTO::class)
                        ->setCurrencySymbol($wallet->currency_symbol)
                        ->setWalletAddress($walletChain->address)
                        ->setBlockchain($currencyChain->blockchain_name->value)
                        ->setContractAddress($currencyChain->contract_address ?? null)

                );
            } catch (\Throwable $e) {
                report($e);
                // Skip this chain if there's an error getting deposits
                continue;
            }

            foreach ($transactions as $transaction) {
                if (Deposit::where('transaction_hash', $transaction->getTransactionHash())->exists()) {
                    continue;
                }
                $transactionHash = $transaction->getTransactionHash();
                try {
                    DB::beginTransaction();

                    $depositStatus = DepositStatusEnum::CONFIRMED;
                    if (Math::comp($transaction->getAmount(), $currencyChain->min_deposit_amount) === -1) {
                        $depositStatus = DepositStatusEnum::TOO_SMALL;
                    }

                    $currency = Currency::whereSymbol($transaction->getCryptocurrency())->first();
                    $usdtValue = Math::mul($currency->exchangePrice, $transaction->getAmount());

                    $deposit = Deposit::create([
                        'user_id' => $requestDTO->getUserId(),
                        'currency_symbol' => $transaction->getCryptocurrency(),
                        'currency_chain_id' => $currencyChain->id,
                        'amount' => $transaction->getAmount(),
                        'address' => $transaction->getWalletAddress(),
                        'transaction_hash' => $transaction->getTransactionHash(),
                        'confirmed_at' => $transaction->getTimestamp(),
                        'status' => $depositStatus,
                        'usdt_value' => $usdtValue,
                    ]);

                    Transaction::create([
                        'user_id' => $requestDTO->getUserId(),
                        'deposit_id' => $deposit->id,
                        'wallet_id' => $wallet->id,
                        'amount' => $transaction->getAmount(),
                        'balance' => $wallet->balance,
                        'coin_price' => $currency->exchangePrice,
                        'type' => TransactionTypeEnum::DEPOSIT,
                        'subtype' => TransactionSubTypeEnum::USER_INITIATED,
                        'status' => TransactionStatusEnum::SUCCESS,
                        'description' => 'واریز به آدرس: ' . $deposit->address . ' هش تراکنش: ' . $transactionHash
                    ]);

                    if ($depositStatus === DepositStatusEnum::CONFIRMED) {
                        $wallet->increment('balance', $transaction->getAmount());
                        DepositDetected::dispatch($requestDTO->getUserId(), [
                            'currency' => $transaction->getCryptocurrency(),
                            'amount' => $transaction->getAmount(),
                            'tx_hash' => $transaction->getTransactionHash(),
                            'status' => 'confirmed',
                        ]);
                    }
                    DB::commit();
                    $hasNewTransaction = true;
                    $transactionCount++;
                    $totalDepositAmount = Math::add($totalDepositAmount, $transaction->getAmount());
                } catch (Throwable $exception) {
                    DB::rollBack();
                    report($exception);
                    throw $exception;
                }
            }
        }

        // ذخیره اطلاعات در سشن
        session([
            'deposit_transaction_count' => $transactionCount,
            'total_deposit_amount' => $totalDepositAmount,
            'currency_symbol' => $requestDTO->getCurrencySymbol()
        ]);

        return $hasNewTransaction;
    }

    /**
     * Check user deposit for a specific wallet chain
     * @throws InternalWalletHasProblemException
     */
    public function checkUserDepositByChain(int $userId, int $walletChainId): bool
    {
        $hasNewTransaction = false;
        $transactionCount = 0;
        $totalDepositAmount = 0;

        $user = User::find($userId);
        $walletChain = \App\Models\WalletChain::with(['wallet.currency.chains'])->find($walletChainId);

        if (!$walletChain || !$walletChain->address) {
            Toast::message('آدرس شبکه یافت نشد.')->warning()->notify();
            return false;
        }

        $wallet = $walletChain->wallet;
        $currencyChain = $wallet->currency->chains->where('chain', $walletChain->currency_chain)->first();

        if (!$currencyChain) {
            Toast::message('شبکه ارز یافت نشد.')->warning()->notify();
            return false;
        }

        try {
            $hdDeposit = resolve(HDWalletFacade::class);

            $transactions = $hdDeposit->getDepositLists(
                resolve(GetDepositListsRequestDTO::class)
                    ->setCurrencySymbol($wallet->currency_symbol)
                    ->setWalletAddress($walletChain->address)
                    ->setBlockchain($currencyChain->blockchain_name->value)
                    ->setContractAddress($currencyChain->contract_address ?? null)
            );
        } catch (\Throwable $e) {
            report($e);
            throw new InternalWalletHasProblemException('سرویس HD Wallet در دسترس نیست. لطفاً بعداً تلاش کنید.');
        }

        foreach ($transactions as $transaction) {
            if (Deposit::where('transaction_hash', $transaction->getTransactionHash())->exists()) {
                continue;
            }
            $transactionHash = $transaction->getTransactionHash();
            try {
                DB::beginTransaction();

                $depositStatus = DepositStatusEnum::CONFIRMED;
                if (Math::comp($transaction->getAmount(), $currencyChain->min_deposit_amount) === -1) {
                    $depositStatus = DepositStatusEnum::TOO_SMALL;
                }

                $currency = Currency::whereSymbol($transaction->getCryptocurrency())->first();
                $usdtValue = Math::mul($currency->exchangePrice, $transaction->getAmount());

                $deposit = Deposit::create([
                    'user_id' => $userId,
                    'currency_symbol' => $transaction->getCryptocurrency(),
                    'currency_chain_id' => $currencyChain->id,
                    'amount' => $transaction->getAmount(),
                    'address' => $transaction->getWalletAddress(),
                    'transaction_hash' => $transaction->getTransactionHash(),
                    'confirmed_at' => $transaction->getTimestamp(),
                    'status' => $depositStatus,
                    'usdt_value' => $usdtValue,
                ]);

                Transaction::create([
                    'user_id' => $userId,
                    'deposit_id' => $deposit->id,
                    'wallet_id' => $wallet->id,
                    'amount' => $transaction->getAmount(),
                    'balance' => $wallet->balance,
                    'coin_price' => $currency->exchangePrice,
                    'type' => TransactionTypeEnum::DEPOSIT,
                    'subtype' => TransactionSubTypeEnum::USER_INITIATED,
                    'status' => TransactionStatusEnum::SUCCESS,
                    'description' => 'واریز به آدرس: ' . $deposit->address . ' هش تراکنش: ' . $transactionHash
                ]);

                if ($depositStatus === DepositStatusEnum::CONFIRMED) {
                    $wallet->increment('balance', $transaction->getAmount());
                    DepositDetected::dispatch($userId, [
                        'currency' => $transaction->getCryptocurrency(),
                        'amount' => $transaction->getAmount(),
                        'tx_hash' => $transaction->getTransactionHash(),
                        'status' => 'confirmed',
                    ]);
                }
                DB::commit();
                $hasNewTransaction = true;
                $transactionCount++;
                $totalDepositAmount = Math::add($totalDepositAmount, $transaction->getAmount());
            } catch (Throwable $exception) {
                DB::rollBack();
                report($exception);
                throw $exception;
            }
        }

        // ذخیره اطلاعات در سشن
        session([
            'deposit_transaction_count' => $transactionCount,
            'total_deposit_amount' => $totalDepositAmount,
            'currency_symbol' => $wallet->currency_symbol,
            'chain_name' => $walletChain->currency_chain
        ]);

        return $hasNewTransaction;
    }

    public function checkDepositWallet()
    {
        $pendingDeposits = Deposit::where('status', DepositStatusEnum::PENDING)->get();

        $hdDeposit = resolve(HDWalletFacade::class);
        foreach ($pendingDeposits as $deposit) {

            $transactions = $hdDeposit->getDepositLists(
                resolve(GetDepositListsRequestDTO::class)
                    ->setCurrencySymbol($deposit->currency_symbol)
                    ->setWalletAddress($deposit->address)
            );

            foreach ($transactions as $transaction) {
                if (Deposit::where('transaction_hash', $transaction->getTransactionHash())->exists()) {
                    continue;
                }
                Deposit::create([
                    'user_id' => $deposit->user_id,
                    'currency_symbol' => $transaction->getCryptocurrency(),
                    'currency_chain_id' => $transaction->getBlockChain(),
                    'amount' => $transaction->getAmount(),
                    'address' => $transaction->getWalletAddress(),
                    'transaction_hash' => $transaction->getTransactionHash(),
                    'confirmed_at' => $transaction->getTimestamp(),
                    'status' => DepositStatusEnum::CONFIRMED
                ]);
            }
        }
    }
}
