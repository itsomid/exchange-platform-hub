<?php

namespace App\Services\Wallet;

use App\Enums\DepositStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Exceptions\V1\Wallet\InternalWalletHasProblemException;
use App\Functions\FlashMessages\Toast;
use App\Helpers\Math;
use App\Infrastructure\HDWallet\DTO\HDDeposit\GetDepositListsRequestDTO;
use App\Infrastructure\HDWallet\HDWalletDepositService;
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
        $user = User::find($requestDTO->getUserId());
        $wallet = Wallet::where('user_id', $requestDTO->getUserId())->where('currency_symbol', $requestDTO->getCurrencySymbol())->first();

        $wallet = $wallet->load('chains.wallet.currency.chains');

        $chains = $wallet->chains;

        if (! $chains->contains(fn($chain) => ! empty($chain->address))) {
            Toast::message('کاربر آدرس زنجیره‌ای ندارد.')->warning()->notify();
            return false;
        }

        $hdDeposit = resolve(HDWalletDepositService::class);

        foreach ($chains as $chain) {
            if (empty($chain->address)) {
                continue;
            }
            $currencyChain = $chain->wallet->currency->chains->where('chain', $chain->currency_chain)->first();

            $transactions = $hdDeposit->getDepositLists(
                resolve(GetDepositListsRequestDTO::class)
                    ->setCurrencySymbol($wallet->currency_symbol)
                    ->setWalletAddress($chain->address)
                    ->setBlockchain($currencyChain->blockchain_name->value)

            );

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
                        'user_id' => $transaction->getUserId(),
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
                        // TODO: Add notification when DepositSuccessful class is created
                        // $user->notify(new DepositSuccessful($transaction->getCryptocurrency(), $transaction->getAmount(), $user->name));
                    }
                    DB::commit();
                    $hasNewTransaction = true;
                } catch (Throwable $exception) {
                    DB::rollBack();
                    report($exception);
                    throw $exception;
                }
            }
        }

        return $hasNewTransaction;
    }

    public function checkDepositWallet()
    {
        $pendingDeposits = Deposit::where('status', DepositStatusEnum::PENDING)->get();

        $hdDeposit = resolve(HDWalletDepositService::class);
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
                    'user_id' => $transaction->getUserId(),
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
