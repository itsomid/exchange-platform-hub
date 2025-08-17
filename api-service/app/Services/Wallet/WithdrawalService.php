<?php

namespace App\Services\Wallet;

use App\Enums\LockedBalanceTypeEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WithdrawalStatusEnum;
use App\Helpers\Math;
use App\Infrastructure\HDWallet\DTO\Withdrawal\GetWithdrawalStatusRequestDTO;
use App\Infrastructure\HDWallet\DTO\Withdrawal\WithdrawRequestDTO;
use App\Infrastructure\HDWallet\Exceptions\NotFoundException;
use App\Infrastructure\HDWallet\HDWalletWithdrawalService;
use App\Exceptions\V1\Wallet\InsufficientAmountForFeeException;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Notifications\WithdrawalSuccessful;
use App\Repositories\Interfaces\CurrencyRepositoryInterface;
use App\Repositories\Interfaces\LockedBalanceRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Repositories\Interfaces\WithdrawalRepositoryInterface;
use App\Repositories\Interfaces\ExchangeRepositoryInterface;
use App\Services\Exchanges\AdminNotification;
use App\Services\Wallet\DTO\Withdrawal\CheckWithdrawalResponseDTO;
use App\Services\Wallet\DTO\Withdrawal\CreateWithdrawalRequestDTO;
use App\Services\Wallet\DTO\Withdrawal\CreateWithdrawalResponseDTO;
use App\Repositories\DTO\Transaction\CreateTransactionRequestDTO;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class WithdrawalService
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly CurrencyRepositoryInterface $currencyRepository,
        private readonly WithdrawalRepositoryInterface $withdrawalRepository,
        private readonly HDWalletWithdrawalService $withdrawalService,
        private readonly LockedBalanceRepositoryInterface $lockedBalanceRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly ExchangeRepositoryInterface $exchangeRepository,
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
            );
            $this->lockBalance($wallet, $amount, $withdrawal->id);

            DB::beginTransaction();
            if ($withdrawalStatus === WithdrawalStatusEnum::AWAITING_APPROVAL) {
                $withdrawal->update([
                    'description' => 'Admin approval required',
                ]);
            } else {
                $withdrawal->update([
                    'description' => 'Withdraw request send to HD Wallet',
                ]);

                $this->withdrawalService->withdraw(
                    resolve(WithdrawRequestDTO::class)
                        ->setAmount($receivedAmount)
                        ->setWithdrawalId($withdrawal->id)
                        ->setWithdrawAddress($requestDTO->getAddress())
                        ->setBlockchain($chain->blockchain_name->value)
                        ->setUserId($requestDTO->getUserId())
                        ->setCurrencySymbol($requestDTO->getCurrencySymbol())
                );
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

    public function checkAllWithdrawal(): void
    {
        $pendingWithdrawal = $this->withdrawalRepository->getAllPending();
        $this->checkWithdrawal($pendingWithdrawal);
    }

    public function checkSpecificUserWithdrawal(int $userId): CheckWithdrawalResponseDTO
    {
        $pendingWithdrawal = $this->withdrawalRepository->getUserAllPending($userId);

        return $this->checkWithdrawal($pendingWithdrawal);
    }

    private function fialedWithdrawal(Withdrawal $withdrawal)
    {

        try {

            DB::beginTransaction();

            $this->lockedBalanceRepository->deleteWithdrawalLockedBalance($withdrawal->id);

            $wallet = Wallet::query()
                ->where('user_id', $withdrawal->user_id)
                ->where('currency_symbol', $withdrawal->currency_symbol)
                ->lockForUpdate()
                ->first();

            $wallet->decrement('locked_balance', $withdrawal->amount);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);
            throw $e;
        }
    }

    public function checkWithdrawal(Collection $pendingWithdrawal): CheckWithdrawalResponseDTO
    {
        $checkWithdrawalResponseDTO = resolve(CheckWithdrawalResponseDTO::class);

        foreach ($pendingWithdrawal as $withdrawal) {
            $user = $withdrawal->user;
            try {

                $responseDTO = $this->withdrawalService->getStatus(
                    resolve(GetWithdrawalStatusRequestDTO::class)
                        ->setWithdrawalId($withdrawal->id)
                        ->setBlockchain($withdrawal->currencyChain->blockchain_name->value)
                        ->setCurrencySymbol($withdrawal->currency_symbol)
                );
                $checkWithdrawalResponseDTO
                    ->setWithdrawId($withdrawal->id)
                    ->setCurrencyChain($withdrawal->currencyChain->chain->value)
                    ->setWalletAddress($withdrawal->address)
                    ->setAmount($withdrawal->amount)
                    ->setTotalFee($withdrawal->total_fee)
                    ->setCurrencySymbol($withdrawal->currency_symbol)
                    ->setExplorerAddressUrl($withdrawal->explorer_address_url)
                    ->setExplorerTxUrl($withdrawal->explorer_tx_url);

                if ($responseDTO->getStatus() === 'failed') {

                    $withdrawal->update([
                        'status' => WithdrawalStatusEnum::FAILED,
                        'description' => $responseDTO->getDescription(),
                    ]);

                    $this->fialedWithdrawal($withdrawal);

                    $checkWithdrawalResponseDTO->setStatus(WithdrawalStatusEnum::FAILED)
                        ->setConfirmedAt($withdrawal->confirmed_at)
                        ->setTransactionHash($responseDTO->getTransactionHash());

                    AdminNotification::sendHotWalletNotEnoughBalance($responseDTO->getCurrencySymbol(), $responseDTO->getAmount(), $user);
                }

                if ($responseDTO->getStatus() === 'completed') {

                    $this->confirmWithdrawal($withdrawal, $responseDTO->getTransactionHash(), $responseDTO->getFee());

                    $user->notify(new WithdrawalSuccessful($withdrawal->currency_symbol, $withdrawal->amount, $user->name, $withdrawal->currencyChain->chain->value));

                    $checkWithdrawalResponseDTO->setStatus(WithdrawalStatusEnum::COMPLETED)
                        ->setTransactionHash($responseDTO->getTransactionHash())
                        ->setConfirmedAt($withdrawal->confirmed_at);
                }
            } catch (NotFoundException) {
                report(new \Exception('Withdrawal #' . $withdrawal->id . ' not found in HD wallet Service'));
                $withdrawal->update([
                    'status' => WithdrawalStatusEnum::FAILED,
                ]);
                $this->fialedWithdrawal($withdrawal);
            } catch (Throwable $exception) {
                report($exception);

                continue;
            }
        }

        return $checkWithdrawalResponseDTO;
    }

    private function confirmWithdrawal(Withdrawal $withdrawal, string $transactionHash, string $hdWalletNetworkFee): void
    {

        try {

            $this->lockedBalanceRepository->deleteWithdrawalLockedBalance($withdrawal->id);


            DB::beginTransaction();
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

            $bitexroomWallet = $this->walletRepository->getBitexroomWalletWithLock($withdrawal->currency_symbol);

            //HD Wallet Fee
            $this->transactionRepository->create(
                resolve(CreateTransactionRequestDTO::class)
                    ->setUserId(config('bitexroom.user_id'))
                    ->setWalletId($bitexroomWallet->id)
                    ->setWithdrawalId($withdrawal->id)
                    ->setAmount(-$hdWalletNetworkFee)
                    ->setBalance(null)
                    ->setCoinPrice($withdrawal->currency->exchangePrice)
                    ->setType(TransactionTypeEnum::FEE)
                    ->setSubtype(TransactionSubTypeEnum::HD_WALLET_FEE)
                    ->setStatus(TransactionStatusEnum::SUCCESS)
                    ->setDescription('کارمزد پرداخت شده به شبکه برای برداشت از HD Wallet.')
            );

            if ($withdrawal->exchange_fee > 0 || $withdrawal->network_fee > 0) {
                $this->createExchangeWithdrawalFee($withdrawal, $hdWalletNetworkFee, $bitexroomWallet);
            }

            DB::commit();
        } catch (Throwable $e) {

            DB::rollBack();
            report($e);
            throw $e;
        }
    }

    private function createExchangeWithdrawalFee($withdrawal, $hdWalletNetworkFee, $bitexroomWallet): void
    {

        $exchangeWithdrawalFee = $withdrawal->exchange_fee;
        $exchangeNetworkFee = $withdrawal->network_fee;
        if ($exchangeWithdrawalFee > 0) {
            $this->transactionRepository->create(
                resolve(CreateTransactionRequestDTO::class)
                    ->setUserId(config('bitexroom.user_id'))
                    ->setWalletId($bitexroomWallet->id)
                    ->setWithdrawalId($withdrawal->id)
                    ->setAmount($exchangeWithdrawalFee)
                    ->setBalance(null)
                    ->setCoinPrice($withdrawal->currency->exchangePrice)
                    ->setType(TransactionTypeEnum::FEE)
                    ->setSubtype(TransactionSubTypeEnum::EXCHANGE_WITHDRAWAL_FEE)
                    ->setStatus(TransactionStatusEnum::SUCCESS)
                    ->setDescription("کارمزد برداشت صرافی  {$bitexroomWallet->currency_symbol} کاربر  " . "(#{$withdrawal->user->id}) " . $withdrawal->user->username)
            );

            $bitexroomWallet->increment('balance', $exchangeWithdrawalFee);
        }
        if ($exchangeNetworkFee > 0) {
            $this->transactionRepository->create(
                resolve(CreateTransactionRequestDTO::class)
                    ->setUserId(config('bitexroom.user_id'))
                    ->setWalletId($bitexroomWallet->id)
                    ->setWithdrawalId($withdrawal->id)
                    ->setAmount($exchangeNetworkFee)
                    ->setBalance(null)
                    ->setExchangeId($this->exchangeRepository->getActiveExchange()->id)
                    ->setCoinPrice($withdrawal->currency->exchangePrice)
                    ->setType(TransactionTypeEnum::FEE)
                    ->setSubtype(TransactionSubTypeEnum::NETWORK_WITHDRAWAL_FEE)
                    ->setStatus(TransactionStatusEnum::SUCCESS)
                    ->setDescription("کارمزد شبکه صرافی  {$bitexroomWallet->currency_symbol} کاربر  " . "(#{$withdrawal->user->id}) " . $withdrawal->user->username)
            );
            $bitexroomWallet->increment('balance', Math::sub($exchangeNetworkFee, $hdWalletNetworkFee));
        }
    }



    public function lockBalance(Wallet $wallet, string $amount, int $withdrawalId): void
    {
        $this->lockedBalanceRepository->createLockedBalance([
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'type' => LockedBalanceTypeEnum::WITHDRAWAL,
            'withdrawal_id' => $withdrawalId,
        ]);
        // Deduct balance and lock funds
        $wallet->increment('locked_balance', $amount);
    }
}
