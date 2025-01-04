<?php

namespace App\Services\Wallet;

use App\Enums\DepositStatusEnum;
use App\Infrastructure\HDWallet\DTO\HDDeposit\GetDepositListsRequestDTO;
use App\Infrastructure\HDWallet\HDDepositService;
use App\Repositories\DTO\Deposit\CreateDepositRequestDTO;
use App\Repositories\Interfaces\DepositRepositoryInterface;

class CheckWalletService
{
    public function __construct(private readonly DepositRepositoryInterface $depositRepository) {}

    public function checkDepositWallet()
    {
        $pendingDeposits = $this->depositRepository->getPendingDeposits();

        $hdDeposit = resolve(HDDepositService::class);
        foreach ($pendingDeposits as $deposit) {

            $transactions = $hdDeposit->getDepositLists(
                resolve(GetDepositListsRequestDTO::class)
                    ->setCurrencySymbol($deposit->currency_symbol)
                    ->setWalletAddress($deposit->address)
            );

            foreach ($transactions as $transaction) {
                if ($this->depositRepository->isDepositExists($transaction->getTransactionHash())) {
                    continue;
                }
                $this->depositRepository->create(resolve(CreateDepositRequestDTO::class)
                    ->setUserId($transaction->getUserId())
                    ->setCurrencySymbol($transaction->getCryptocurrency())
                    ->setCurrencyChain($transaction->getBlockChain())
                    ->setAmount($transaction->getAmount())
                    ->setAddress($transaction->getWalletAddress())
                    ->setTransactionHash($transaction->getTransactionHash())
                    ->setConfirmedAt($transaction->getTimestamp())
                    ->setStatus(DepositStatusEnum::CONFIRMED)
                );

            }
        }
    }
}
