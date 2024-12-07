<?php

namespace App\Services\Wallet;

use App\Enums\BalanceOperationEnum;
use App\Repositories\Interfaces\WalletChainRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Services\Wallet\DTO\Wallet\GenerateAddressRequestDTO;
use App\Services\Wallet\DTO\Wallet\GenerateAddressResponseDTO;
use App\Services\Wallet\DTO\Wallet\UpdateBalanceRequestDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class WalletService
{
    public function __construct(private readonly WalletRepositoryInterface $walletRepository, private readonly WalletChainRepositoryInterface $walletChainRepository) {}

    public function generateAddress(GenerateAddressRequestDTO $requestDTO): GenerateAddressResponseDTO
    {
        $wallet = $this->walletRepository->createOrGetWallet(
            $requestDTO->getCurrency(),
            $requestDTO->getUserId()
        );

        $chain = $this->walletChainRepository->createOrGetChain(
            $wallet->id,
            $requestDTO->getCurrency()
        );

        if (is_null($chain->public_key)) {
            //Generate Public Key
            $this->walletChainRepository->savePublicKey(
                $chain->id,
                $address = Str::random(24)
            );
        } else {
            $address = $chain->public_key;
        }

        return resolve(GenerateAddressResponseDTO::class)
            ->setAddress($address);
    }

    public function updateBalance(UpdateBalanceRequestDTO $requestDTO): bool
    {

        try {
            DB::beginTransaction();

            $wallet = $this->walletRepository
                ->getWalletWithLock($requestDTO->getCurrencySymbol(), $requestDTO->getUserId());

            $balance = $wallet->balance;
            if ($requestDTO->getOperation() === BalanceOperationEnum::Increase) {
                $balance = bcadd($wallet->balance, $requestDTO->getAmount(), 8);
            } elseif ($requestDTO->getOperation() === BalanceOperationEnum::Decrease) {
                $balance = bcsub($wallet->balance, $requestDTO->getAmount(), 8);
            }

            $this->walletRepository->updateBalance($requestDTO->getCurrencySymbol(), $requestDTO->getUserId(), $balance);

            DB::commit();

            return true;
        } catch (Throwable $exception) {
            report($exception);
            DB::rollBack();

            return false;
        }

    }
}
