<?php

namespace App\Infrastructure\HDWallet\DTO\HDDeposit;

use Carbon\Carbon;

class GetDepositListsResponseDTO
{
    private int $walletId;

    private int $userId;

    private Carbon $timestamp;

    private string $cryptocurrency;

    private string $amount;

    private string $transactionHash;

    private string $status;

    private int $confirmationBlocks;

    private string $blockChain;

    private string $walletAddress;

    public function setWalletId(int $walletId): GetDepositListsResponseDTO
    {
        $this->walletId = $walletId;

        return $this;
    }

    public function getWalletId(): int
    {
        return $this->walletId;
    }

    public function setUserId(int $userId): GetDepositListsResponseDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setTimestamp(string $timestamp): GetDepositListsResponseDTO
    {
        $this->timestamp = Carbon::parse($timestamp);

        return $this;
    }

    public function getTimestamp(): Carbon
    {
        return $this->timestamp;
    }

    public function setCryptocurrency(string $cryptocurrency): GetDepositListsResponseDTO
    {
        $this->cryptocurrency = $cryptocurrency;

        return $this;
    }

    public function getCryptocurrency(): string
    {
        return $this->cryptocurrency;
    }

    public function setAmount(string $amount): GetDepositListsResponseDTO
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setTransactionHash(string $transactionHash): GetDepositListsResponseDTO
    {
        $this->transactionHash = $transactionHash;

        return $this;
    }

    public function getTransactionHash(): string
    {
        return $this->transactionHash;
    }

    public function setStatus(string $status): GetDepositListsResponseDTO
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setConfirmationBlocks(int $confirmationBlocks): GetDepositListsResponseDTO
    {
        $this->confirmationBlocks = $confirmationBlocks;

        return $this;
    }

    public function getConfirmationBlocks(): int
    {
        return $this->confirmationBlocks;
    }

    public function setBlockChain(string $blockChain): GetDepositListsResponseDTO
    {
        $this->blockChain = $blockChain;

        return $this;
    }

    public function getBlockChain(): string
    {
        return $this->blockChain;
    }

    public function setWalletAddress(string $walletAddress): GetDepositListsResponseDTO
    {
        $this->walletAddress = $walletAddress;

        return $this;
    }

    public function getWalletAddress(): string
    {
        return $this->walletAddress;
    }
}
