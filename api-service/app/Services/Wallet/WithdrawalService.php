<?php

namespace App\Services\Wallet;

use App\Enums\WithdrawalStatusEnum;
use App\Models\CurrencyChain;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Repositories\Interfaces\CurrencyRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Services\Wallet\DTO\Withdrawal\CreateWithdrawalRequestDTO;
use App\Services\Wallet\DTO\Withdrawal\CreateWithdrawalResponseDTO;
use Illuminate\Support\Facades\DB;

class WithdrawalService
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly CurrencyRepositoryInterface $currencyRepository,

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
            $fee = CurrencyChain::totalWithdrawalFee($requestDTO->getCurrencyChain());
            $amount = $requestDTO->getAmount();

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
            $withdrawal = Withdrawal::query()->create([
                'user_id' => $requestDTO->getUserId(),
                'currency_chain' => $requestDTO->getCurrencyChain(),
                'currency_symbol' => $requestDTO->getCurrencySymbol(),
                'amount' => $amount,
                'fee' => $fee,
                'address' => $requestDTO->getAddress(),
                'status' => $withdrawalStatus,
            ]);
            if ($withdrawalStatus === WithdrawalStatusEnum::AWAITING_APPROVAL) {
                $withdrawal->update([
                    'description' => 'Admin approval required',
                ]);
            } else {
                $withdrawal->update([
                    'description' => 'Withdraw request send to HD Wallet',
                ]);
                //TODO: Send Withdraw request to HD Wallet
            }
            DB::commit();

            return resolve(CreateWithdrawalResponseDTO::class)
                ->setReceivedAmount(bcsub($amount, $fee, config('bitexroom.scale_precision')))
                ->setFee($fee)
                ->setStatus($withdrawalStatus);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
