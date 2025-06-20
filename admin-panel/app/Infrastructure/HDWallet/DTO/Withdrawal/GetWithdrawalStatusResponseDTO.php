<?php

namespace App\Infrastructure\HDWallet\DTO\Withdrawal;

use Carbon\Carbon;

class GetWithdrawalStatusResponseDTO
{
    private string $currencySymbol;

    private string $blockchain;

    private int $withdrawalId;

    private int $userId;

    private string $amount;

    private string $withdrawAddress;

    private ?string $memo = null;

    private ?string $remarks = null;

    private ?string $transactionHash = null;

    private ?int $blockNumber = null;

    private string $status;

    private ?Carbon $timestamp = null;

    private ?string $fee = null;

    private ?string $description = null;

    public function setCurrencySymbol(string $currencySymbol): GetWithdrawalStatusResponseDTO
    {
        $this->currencySymbol = $currencySymbol;

        return $this;
    }

    public function getCurrencySymbol(): string
    {
        return $this->currencySymbol;
    }

    public function setBlockchain(string $blockchain): GetWithdrawalStatusResponseDTO
    {
        $this->blockchain = $blockchain;

        return $this;
    }

    public function getBlockchain(): string
    {
        return $this->blockchain;
    }

    public function setWithdrawalId(int $withdrawalId): GetWithdrawalStatusResponseDTO
    {
        $this->withdrawalId = $withdrawalId;

        return $this;
    }

    public function getWithdrawalId(): int
    {
        return $this->withdrawalId;
    }

    public function setUserId(int $userId): GetWithdrawalStatusResponseDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setAmount(string $amount): GetWithdrawalStatusResponseDTO
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setWithdrawAddress(string $withdrawAddress): GetWithdrawalStatusResponseDTO
    {
        $this->withdrawAddress = $withdrawAddress;

        return $this;
    }

    public function getWithdrawAddress(): string
    {
        return $this->withdrawAddress;
    }

    public function setTransactionHash(?string $transactionHash): GetWithdrawalStatusResponseDTO
    {
        $this->transactionHash = $transactionHash;

        return $this;
    }

    public function getTransactionHash(): ?string
    {
        return $this->transactionHash;
    }

    public function setBlockNumber(?int $blockNumber): GetWithdrawalStatusResponseDTO
    {
        $this->blockNumber = $blockNumber;

        return $this;
    }

    public function getBlockNumber(): ?int
    {
        return $this->blockNumber;
    }

    public function setStatus(string $status): GetWithdrawalStatusResponseDTO
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setTimestamp(?string $timestamp): GetWithdrawalStatusResponseDTO
    {
        if (! is_null($timestamp)) {
            $this->timestamp = Carbon::createFromTimestamp($timestamp);
        }

        return $this;
    }

    public function getTimestamp(): ?Carbon
    {
        return $this->timestamp;
    }

    public function setFee(?string $fee): GetWithdrawalStatusResponseDTO
    {
        $this->fee = $fee;

        return $this;
    }

    public function getFee(): ?string
    {
        return $this->fee;
    }

    public function setDescription(?string $description): GetWithdrawalStatusResponseDTO
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setMemo(?string $memo): GetWithdrawalStatusResponseDTO
    {
        $this->memo = $memo;

        return $this;
    }

    public function getMemo(): ?string
    {
        return $this->memo;
    }

    public function setRemarks(?string $remarks): GetWithdrawalStatusResponseDTO
    {
        $this->remarks = $remarks;

        return $this;
    }

    public function getRemarks(): ?string
    {
        return $this->remarks;
    }
}
