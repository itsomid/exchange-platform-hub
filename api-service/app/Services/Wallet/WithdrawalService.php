<?php

namespace App\Services\Wallet;

use App\Enums\LockedBalanceTypeEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WithdrawalStatusEnum;
use App\Helpers\Math;

use App\Infrastructure\HDWalletNew\HDWalletFacade;
use App\Exceptions\V1\Wallet\InsufficientAmountForFeeException;
use App\Jobs\SendWithdrawalToHDWallet;
use App\Models\Wallet;
use App\Models\Withdrawal;

use App\Repositories\Interfaces\CurrencyRepositoryInterface;
use App\Repositories\Interfaces\LockedBalanceRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Repositories\Interfaces\WithdrawalRepositoryInterface;
use App\Repositories\Interfaces\ExchangeRepositoryInterface;

use App\Services\Wallet\DTO\Withdrawal\CreateWithdrawalRequestDTO;
use App\Services\Wallet\DTO\Withdrawal\CreateWithdrawalResponseDTO;
use App\Services\Currency\CurrencyService;
use App\Repositories\DTO\Transaction\CreateTransactionRequestDTO;

use Illuminate\Support\Facades\DB;
use Throwable;

class WithdrawalService
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly CurrencyRepositoryInterface $currencyRepository,
        private readonly WithdrawalRepositoryInterface $withdrawalRepository,
        private readonly HDWalletFacade $hdWalletService,
        private readonly LockedBalanceRepositoryInterface $lockedBalanceRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly ExchangeRepositoryInterface $exchangeRepository,
        private readonly CurrencyService $currencyService,
    ) {}

    public function createWithdrawal(CreateWithdrawalRequestDTO $requestDTO): CreateWithdrawalResponseDTO
    {       
        try {

            // Fetch the wallet
       
            $wallet = $this->walletRepository->getWalletWithLock(
                $requestDTO->getCurrencySymbol(),
                $requestDTO->getUserId()
            );
            $currency = $this->currencyRepository->getOne($requestDTO->getCurrencySymbol());
            $chain = $currency->chains()->where('chain', $requestDTO->getCurrencyChain())->first();

            $fee = Math::add($chain->network_fee, $chain->exchange_withdrawal_fee);
            $amount = $requestDTO->getAmount();
            $receivedAmount = Math::sub($amount, $fee);
            if (Math::comp($receivedAmount, 0) === -1 || Math::comp($receivedAmount, 0) === 0) {
                throw new InsufficientAmountForFeeException();
            }
            $value_in_usdt = Math::mul($currency->exchangePrice, $amount);

            $withdrawalStatus = WithdrawalStatusEnum::PENDING;
            if (
                Math::comp($requestDTO->getAmount(), $currency->max_auto_withdraw_amount) === 0 ||
                Math::comp($requestDTO->getAmount(), $currency->max_auto_withdraw_amount) === 1
            ) {
                $withdrawalStatus = WithdrawalStatusEnum::AWAITING_APPROVAL;
            }

            // Create the withdrawal record
            $withdrawal = $this->withdrawalRepository->create(
                resolve(\App\Repositories\DTO\Withdrawal\CreateWithdrawalRequestDTO::class)
                    ->setUserId($requestDTO->getUserId())
                    ->setCurrencyChainId($chain->id)
                    ->setCurrencySymbol($requestDTO->getCurrencySymbol())
                    ->setAmount($amount)
                    ->setUSDTValue($value_in_usdt)
                    ->setAddress($requestDTO->getAddress())
                    ->setNetworkFee($chain->network_fee)
                    ->setExchangeFee($chain->exchange_withdrawal_fee)
                    ->setStatus($withdrawalStatus)
                    ->setRemark($requestDTO->getRemark())
            );
            $this->lockBalance($wallet, $amount, $withdrawal->id);

            DB::beginTransaction();
            if ($withdrawalStatus === WithdrawalStatusEnum::AWAITING_APPROVAL) {
                $withdrawal->update([
                    'description' => 'Admin approval required',
                ]);
            } else {
                // Update status to queued
                $withdrawal->update([
                    'status' => WithdrawalStatusEnum::QUEUED,
                    'description' => 'Withdrawal queued for processing'
                ]);

                // Dispatch job to process withdrawal

                SendWithdrawalToHDWallet::dispatch($withdrawal->id);
            }
            DB::commit();

            return resolve(CreateWithdrawalResponseDTO::class)
                ->setId($withdrawal->id)
                ->setReceivedAmount(Math::sub($amount, $fee))
                ->setFee($fee)
                ->setStatus($withdrawalStatus);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    public function confirmWithdrawal(Withdrawal $withdrawal, string $transactionHash, string $hdWalletNetworkFee): void
    {

        try {

            DB::beginTransaction();

            $this->lockedBalanceRepository->deleteWithdrawalLockedBalance($withdrawal->id);
            $wallet = Wallet::query()
                ->where('user_id', $withdrawal->user_id)
                ->where('currency_symbol', $withdrawal->currency_symbol)
                ->lockForUpdate()
                ->first();

            // Update withdrawal record
            $withdrawal->update([
                'transaction_hash' => $transactionHash,
                'status' => WithdrawalStatusEnum::COMPLETED,
                'hd_wallet_network_fee' => $hdWalletNetworkFee,
                'confirmed_at' => now(),
                'description' => 'Withdraw Completed',
            ]);


            // Unlock funds and deduct locked balance
            $wallet->decrement('balance', $withdrawal->amount);
            $wallet->decrement('locked_balance', $withdrawal->amount);

            // Create the transaction record
            $this->transactionRepository->create(
                resolve(CreateTransactionRequestDTO::class)
                    ->setUserId($withdrawal->user_id)
                    ->setWalletId($wallet->id)
                    ->setWithdrawalId($withdrawal->id)
                    ->setAmount(-$withdrawal->amount)
                    ->setBalance(Math::add($wallet->balance, $withdrawal->amount))
                    ->setCoinPrice($withdrawal->currency->exchangePrice)
                    ->setType(TransactionTypeEnum::WITHDRAWAL)
                    ->setSubtype(TransactionSubTypeEnum::USER_INITIATED)
                    ->setStatus(TransactionStatusEnum::SUCCESS)
                    ->setDescription('برداشت به آدرس: ' . $withdrawal->address . ' هش تراکنش: ' . $transactionHash)
            );

            // Get parent coin for network fee calculation
            $parentCoin = $this->currencyService->getParentCoin($withdrawal->currencyChain);
            $networkFeeCoinPrice = $parentCoin ? $parentCoin->exchangePrice : $withdrawal->currency->exchangePrice;

            // Get the appropriate wallet for HD Wallet Fee (parent coin wallet if exists, otherwise current coin wallet)
            $hdWalletFeeSymbol = $parentCoin ? $parentCoin->symbol : $withdrawal->currency_symbol;
            $exchangeWallet = $this->walletRepository->getExchangeWalletWithLock($hdWalletFeeSymbol);

            //HD Wallet Fee
            $this->transactionRepository->create(
                resolve(CreateTransactionRequestDTO::class)
                    ->setUserId(config('bitexroom.user_id'))
                    ->setWalletId($exchangeWallet->id)
                    ->setWithdrawalId($withdrawal->id)
                    ->setAmount(-$hdWalletNetworkFee)
                    ->setBalance($exchangeWallet->balance)
                    ->setCoinPrice($networkFeeCoinPrice)
                    ->setType(TransactionTypeEnum::FEE)
                    ->setSubtype(TransactionSubTypeEnum::HD_WALLET_FEE)
                    ->setStatus(TransactionStatusEnum::SUCCESS)
                    ->setDescription('کارمزد پرداخت شده به شبکه برای برداشت از HD Wallet')
            );

            if ($withdrawal->exchange_fee > 0 || $withdrawal->network_fee > 0) {
                $this->createExchangeWithdrawalFee($withdrawal, $hdWalletNetworkFee, $exchangeWallet);
            }

            DB::commit();
        } catch (Throwable $e) {

            DB::rollBack();
            report($e);
            throw $e;
        }
    }

    private function createExchangeWithdrawalFee($withdrawal, $hdWalletNetworkFee, $exchangeWallet): void
    {

        $exchangeWithdrawalTotalFee = $withdrawal->total_fee;

        // Get the appropriate wallet for Exchange Withdrawal Fee (based on the actual withdrawal coin)
        $exchangeFeeWallet = $this->walletRepository->getExchangeWalletWithLock($withdrawal->currency_symbol);

        if ($exchangeWithdrawalTotalFee > 0) {
            $this->transactionRepository->create(
                resolve(CreateTransactionRequestDTO::class)
                    ->setUserId(config('bitexroom.user_id'))
                    ->setWalletId($exchangeFeeWallet->id)
                    ->setWithdrawalId($withdrawal->id)
                    ->setAmount($exchangeWithdrawalTotalFee)
                    ->setBalance($exchangeFeeWallet->balance)
                    ->setCoinPrice($withdrawal->currency->exchangePrice)
                    ->setType(TransactionTypeEnum::FEE)
                    ->setSubtype(TransactionSubTypeEnum::EXCHANGE_WITHDRAWAL_FEE)
                    ->setStatus(TransactionStatusEnum::SUCCESS)
                    ->setDescription("کارمزد برداشت صرافی  {$exchangeFeeWallet->currency_symbol} کاربر  " . "(#{$withdrawal->user->id}) " . $withdrawal->user->username . " حاصل فی برداشت صرافی بعلاوه فی برداشت در صرافی مرجع")
            );

            $exchangeFeeWallet->increment('balance', $exchangeWithdrawalTotalFee);
        }
    }


    public function lockBalance(Wallet $wallet, string $amount, int $withdrawalId): void
    {
        $this->lockedBalanceRepository->createLockedBalance([
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'type' => LockedBalanceTypeEnum::WITHDRAWAL,
            'description' => 'مسدود سازی دارایی بابت برداشت #' . $withdrawalId,
            'withdrawal_id' => $withdrawalId,
        ]);
        // Deduct balance and lock funds
        $wallet->increment('locked_balance', $amount);
    }
}
