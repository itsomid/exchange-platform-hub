<?php

namespace App\Services\Wallet;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WithdrawalStatusEnum;
use App\Helpers\Math;
use App\Infrastructure\HDWallet\DTO\Withdrawal\GetWithdrawalStatusRequestDTO;
use App\Infrastructure\HDWallet\DTO\Withdrawal\WithdrawRequestDTO;
use App\Infrastructure\HDWallet\Exceptions\NotFoundException;
use App\Infrastructure\HDWallet\HDWalletWithdrawalService;
use App\Models\CurrencyChain;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Notifications\WithdrawalSuccessful;
use App\Repositories\Interfaces\CurrencyRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Repositories\Interfaces\WithdrawalRepositoryInterface;
use App\Services\Exchanges\AdminNotification;
use App\Services\Wallet\DTO\Withdrawal\CheckWithdrawalResponseDTO;
use App\Services\Wallet\DTO\Withdrawal\CreateWithdrawalRequestDTO;
use App\Services\Wallet\DTO\Withdrawal\CreateWithdrawalResponseDTO;
use Illuminate\Support\Facades\DB;
use Throwable;

class WithdrawalService
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly CurrencyRepositoryInterface $currencyRepository,
        private readonly WithdrawalRepositoryInterface $withdrawalRepository,
        private readonly HDWalletWithdrawalService $withdrawalService,
        private readonly UserRepositoryInterface $userRepository,

    ) {}

    public function createWithdrawal(CreateWithdrawalRequestDTO $requestDTO): CreateWithdrawalResponseDTO
    {
        try {
            DB::beginTransaction();
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
            $value_in_usdt = Math::mul($currency->exchangePrice, $amount);

            $withdrawalStatus = WithdrawalStatusEnum::PENDING;
            if (
                Math::comp($requestDTO->getAmount(), $currency->max_auto_withdraw_amount) === 0 ||
                Math::comp($requestDTO->getAmount(), $currency->max_auto_withdraw_amount) === 1
            ) {
                $withdrawalStatus = WithdrawalStatusEnum::AWAITING_APPROVAL;
            }

            // Deduct balance and lock funds
            $wallet->decrement('balance', $amount);
            $wallet->increment('locked_balance', $amount);

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

    public function checkWithdrawal(int $userId): CheckWithdrawalResponseDTO
    {
        $checkWithdrawalResponseDTO = resolve(CheckWithdrawalResponseDTO::class);

        $user = $this->userRepository->getUserById($userId);

        $pendingWithdrawal = $this->withdrawalRepository->getAllPending($userId);


        foreach ($pendingWithdrawal as $withdrawal) {
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

                    $this->fialedWithdrawalAndUnlockBalance($withdrawal);

                    $checkWithdrawalResponseDTO->setStatus(WithdrawalStatusEnum::FAILED)
                        ->setConfirmedAt($withdrawal->confirmed_at)
                        ->setTransactionHash($responseDTO->getTransactionHash());

                    AdminNotification::sendHotWalletNotEnoughBalance($responseDTO->getCurrencySymbol(), $responseDTO->getAmount());

                    continue;
                }

                if ($responseDTO->getStatus() === 'completed') {

                    $this->confirmWithdrawal($withdrawal, $responseDTO->getTransactionHash(), $responseDTO->getFee());

                    $user->notify(new WithdrawalSuccessful($withdrawal->currency_symbol, $withdrawal->amount, $user->name, $withdrawal->currencyChain->chain->value));

                    $checkWithdrawalResponseDTO->setStatus(WithdrawalStatusEnum::COMPLETED)
                        ->setTransactionHash($responseDTO->getTransactionHash())
                        ->setConfirmedAt($withdrawal->confirmed_at);
                }

            } catch (NotFoundException) {
                $withdrawal->update([
                    'status' => WithdrawalStatusEnum::FAILED,
                ]);
            } catch (Throwable $exception) {
                report($exception);

                continue;
            }

        }

        return $checkWithdrawalResponseDTO;
    }

    private function fialedWithdrawalAndUnlockBalance(Withdrawal $withdrawal)
    {

        try {

            DB::beginTransaction();
            $wallet = Wallet::query()
                ->where('user_id', $withdrawal->user_id)
                ->where('currency_symbol', $withdrawal->currency_symbol)
                ->lockForUpdate()
                ->first();
            $wallet->decrement('locked_balance', $withdrawal->amount);
            $wallet->increment('balance', $withdrawal->amount);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);
            throw $e;
        }
    }

    private function confirmWithdrawal(Withdrawal $withdrawal, string $transactionHash, string $hdWalletNetworkFee): void
    {

        try {
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
            $wallet->decrement('locked_balance', $withdrawal->amount);

            // Create the transaction record
            Transaction::query()->create([
                'user_id' => $withdrawal->user_id,
                'wallet_id' => $wallet->id,
                'withdrawal_id' => $withdrawal->id,
                'amount' => -$withdrawal->amount,
                'balance' => $wallet->balance,
                'type' => TransactionTypeEnum::WITHDRAWAL,
                'subtype' => TransactionSubTypeEnum::USER_INITIATED,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => 'برداشت به آدرس: '.$withdrawal->address.' هش تراکنش: '.$transactionHash,
                'admin_description' => '',
            ]);
            $baseCoinChain = CurrencyChain::query()
                ->where('chain', $withdrawal->currencyChain->chain)
                ->where('is_base_coin', 1)
                ->first();
            $wallet = Wallet::query()
                ->where('currency_symbol', $baseCoinChain->currency->symbol)
                ->where('user_id', config('bitexroom.bitexroom_user_id'))
                ->first();
            //HD Wallet Fee
            Transaction::query()
                ->create([
                    'user_id' => config('bitexroom.bitexroom_user_id'),
                    'wallet_id' => $wallet->id,
                    'withdrawal_id' => $withdrawal->id,
                    'amount' => -$hdWalletNetworkFee,
                    'balance' => $wallet->balance,
                    'type' => TransactionTypeEnum::FEE,
                    'subtype' => TransactionSubTypeEnum::HD_WALLET_FEE,
                    'status' => TransactionStatusEnum::SUCCESS,
                    'description' => 'کارمزد شبکه برداشت به آدرس: '.$withdrawal->address.' هش تراکنش: '.$transactionHash,
                ]);
            $this->createExchangeWithdrawalFee($withdrawal, $hdWalletNetworkFee);

            DB::commit();
        } catch (Throwable $e) {

            DB::rollBack();
            report($e);
            throw $e;
        }
    }

    private function createExchangeWithdrawalFee($withdrawal, $hdWalletNetworkFee): void
    {

        $exchangeWallet = $this->walletRepository->getBitexroomWalletWithLock($withdrawal->currency_symbol);

        $exchangeWithdrawalFee = $withdrawal->exchange_fee;
        $exchangeNetworkFee = $withdrawal->network_fee;
        if ($exchangeWithdrawalFee > 0) {
            Transaction::query()->create([
                'user_id' => config('bitexroom.bitexroom_user_id'),
                'wallet_id' => $exchangeWallet->id,
                'withdrawal_id' => $withdrawal->id,
                'balance' => $exchangeWallet->balance,
                'amount' => $exchangeWithdrawalFee,
                'type' => TransactionTypeEnum::FEE,
                'subtype' => TransactionSubTypeEnum::WITHDRAWAL_EXCHANGE_FEE,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => "کارمزد برداشت صرافی  {$exchangeWallet->currency_symbol} کاربر  "."(#{$withdrawal->user->id}) ".$withdrawal->user->username,
            ]);

            $exchangeWallet->increment('balance', $exchangeWithdrawalFee);
        }
        if ($exchangeNetworkFee > 0) {
            // Exchange Network Fee
            Transaction::query()->create([
                'user_id' => config('bitexroom.bitexroom_user_id'),
                'wallet_id' => $exchangeWallet->id,
                'withdrawal_id' => $withdrawal->id,
                'amount' => $exchangeNetworkFee,
                'balance' => $exchangeWallet->balance,
                'type' => TransactionTypeEnum::FEE,
                'subtype' => TransactionSubTypeEnum::WITHDRAWAL_NETWORK_FEE,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => "کارمزد شبکه صرافی  {$exchangeWallet->currency_symbol} کاربر  "."(#{$withdrawal->user->id}) ".$withdrawal->user->username,
                'admin_description' => '',
            ]);
            $exchangeWallet->increment('balance', Math::sub($exchangeNetworkFee, $hdWalletNetworkFee));
        }
    }
}
