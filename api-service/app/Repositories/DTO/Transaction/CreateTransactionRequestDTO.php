<?php

namespace App\Repositories\DTO\Transaction;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;

class CreateTransactionRequestDTO
{
    private int $userId;

    private int $walletId;

    private ?int $depositId = null;

    private ?int $withdrawalId = null;

    private ?int $otcOrderId = null;

    private ?int $spotTradeId = null;

    private ?string $balance = null;

    private string $amount;

    private TransactionTypeEnum $type;

    private TransactionSubTypeEnum $subtype;

    private TransactionStatusEnum $status;

    private string $description;

    public function setUserId(int $userId): CreateTransactionRequestDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setWalletId(int $walletId): CreateTransactionRequestDTO
    {
        $this->walletId = $walletId;

        return $this;
    }

    public function getWalletId(): int
    {
        return $this->walletId;
    }

    public function setOtcOrderId(?int $otcOrderId): CreateTransactionRequestDTO
    {
        $this->otcOrderId = $otcOrderId;

        return $this;
    }

    public function getOtcOrderId(): ?int
    {
        return $this->otcOrderId;
    }

    public function setBalance(?string $balance): CreateTransactionRequestDTO
    {
        $this->balance = $balance;

        return $this;
    }

    public function getBalance(): ?string
    {
        return $this->balance;
    }

    public function setAmount(string $amount): CreateTransactionRequestDTO
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setType(TransactionTypeEnum $type): CreateTransactionRequestDTO
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): TransactionTypeEnum
    {
        return $this->type;
    }

    public function setSubtype(TransactionSubTypeEnum $subtype): CreateTransactionRequestDTO
    {
        $this->subtype = $subtype;

        return $this;
    }

    public function getSubtype(): TransactionSubTypeEnum
    {
        return $this->subtype;
    }

    public function setStatus(TransactionStatusEnum $status): CreateTransactionRequestDTO
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): TransactionStatusEnum
    {
        return $this->status;
    }

    public function setDescription(string $description): CreateTransactionRequestDTO
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDepositId(?int $depositId): CreateTransactionRequestDTO
    {
        $this->depositId = $depositId;

        return $this;
    }

    public function getDepositId(): ?int
    {
        return $this->depositId;
    }

    public function setWithdrawalId(?int $withdrawalId): CreateTransactionRequestDTO
    {
        $this->withdrawalId = $withdrawalId;

        return $this;
    }

    public function getWithdrawalId(): ?int
    {
        return $this->withdrawalId;
    }

    public function setSpotTradeId(?int $spotTradeId): CreateTransactionRequestDTO
    {
        $this->spotTradeId = $spotTradeId;

        return $this;
    }

    public function getSpotTradeId(): ?int
    {
        return $this->spotTradeId;
    }
}
