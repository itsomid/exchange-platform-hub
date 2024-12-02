<?php

namespace App\Services\Wallet;

use App\Repositories\Interfaces\WalletChainRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Services\Wallet\DTO\Wallet\GenerateAddressRequestDTO;
use App\Services\Wallet\DTO\Wallet\GenerateAddressResponseDTO;
use Illuminate\Support\Str;

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
}
