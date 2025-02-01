<?php

namespace App\Services\Wallet;

use App\Enums\CurrencyChainEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\WithdrawalStatusEnum;
use App\Infrastructure\HDWallet\DTO\Withdrawal\GetWithdrawalStatusRequestDTO;
use App\Infrastructure\HDWallet\DTO\Withdrawal\WithdrawRequestDTO;
use App\Infrastructure\HDWallet\Exceptions\NotFoundException;
use App\Infrastructure\HDWallet\HDWalletWithdrawalService;
use App\Models\CurrencyChain;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Repositories\Interfaces\CurrencyRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Repositories\Interfaces\WithdrawalRepositoryInterface;
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

            $fee = bcadd(toDecimalString($chain->network_fee), toDecimalString($chain->exchange_withdrawal_fee), 8);
            $amount = $requestDTO->getAmount();
            $receivedAmount = bcsub($amount, $fee, 8);

            $withdrawalStatus = WithdrawalStatusEnum::PENDING;
            if (
                bccomp($requestDTO->getAmount(), $currency->max_auto_withdraw_amount, config('bitexroom.scale_precision')) === 0 ||
                bccomp($requestDTO->getAmount(), $currency->max_auto_withdraw_amount, config('bitexroom.scale_precision')) === 1
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
                    ->setCurrencyChain($requestDTO->getCurrencyChain())
                    ->setCurrencySymbol($requestDTO->getCurrencySymbol())
                    ->setAmount($amount)
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
                ->setReceivedAmount(bcsub($amount, $fee, config('bitexroom.scale_precision')))
                ->setFee($fee)
                ->setStatus($withdrawalStatus);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function checkWithdrawal(): void
    {
        $pending = $this->withdrawalRepository->getAllPending();

        foreach ($pending as $withdrawal) {

            $chain = $withdrawal->currency->chains->where('chain', CurrencyChainEnum::tryFrom('BSC'))->first();

            try {
                dd($withdrawal->currency->chains);
                $responseDTO = $this->withdrawalService->getStatus(
                    resolve(GetWithdrawalStatusRequestDTO::class)
                        ->setWithdrawalId($withdrawal->id)
                        ->setBlockchain($chain->blockchain_name->value)
                        ->setCurrencySymbol($withdrawal->currency_symbol)
                );

                if ($responseDTO->getStatus() === 'failed') {
                    $withdrawal->update([
                        'status' => WithdrawalStatusEnum::FAILED,
                    ]);

                    continue;
                }
                if ($responseDTO->getStatus() === 'completed') {
                    $this->confirmWithdrawal($withdrawal, $responseDTO->getTransactionHash(), $responseDTO->getFee());
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

    }

    private function confirmWithdrawal(Withdrawal $withdrawal, string $transactionHash, string $hdWalletNetworkFee): void
    {
        try {
            DB::beginTransaction();
            $wallet = Wallet::query()->where('user_id', $withdrawal->user_id)->where('currency_symbol', $withdrawal->currency_symbol)->first();

            // Update withdrawal record
            $withdrawal->update([
                'transaction_hash' => $transactionHash,
                'status' => WithdrawalStatusEnum::COMPLETED,
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
                ->where('chain', $withdrawal->currency_chain)
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
                'subtype' => TransactionSubTypeEnum::WITHDRAWAL_FEE,
                'status' => TransactionStatusEnum::SUCCESS,
                'description' => "کارمزد برداشت صرافی  {$exchangeWallet->currency_symbol} کاربر  "."(#{$withdrawal->user->id}) ".$withdrawal->user->username,
            ]);

            $exchangeWallet->increment('balance', $exchangeWithdrawalFee);
        }
        if ($exchangeNetworkFee > 0) {
            // Exchange Network Fee
            Transaction::query()->create([
                'user_id' => $withdrawal->user_id,
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
            $exchangeWallet->increment('balance', bcsub(toDecimalString($exchangeNetworkFee), toDecimalString($hdWalletNetworkFee), 8));
        }
    }
}
