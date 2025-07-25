<?php

namespace App\Infrastructure\HDWallet\DTO\HDDeposit;

use Carbon\Carbon;

class GetDepositListsResponseDTO
{

    private Carbon $timestamp;

    private string $cryptocurrency;

    private string $amount;

    private string $transactionHash;

    private string $status;

    private int $confirmationBlocks;

    private string $blockChain;

    private string $walletAddress;

    private ?string $contractAddress = null;

    private string $type;

    private int $blockNumber;

    private string $from;

    private string $to;

    private float $gasPrice;

    private int $gasUsed;


    public function setTimestamp(int $timestamp): GetDepositListsResponseDTO
    {
        $this->timestamp = Carbon::createFromTimestamp($timestamp);

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

    public function setAmount(string|float $amount): GetDepositListsResponseDTO
    {
        $this->amount = (string) $amount;

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

    public function setContractAddress(?string $contractAddress): GetDepositListsResponseDTO
    {
        $this->contractAddress = $contractAddress;

        return $this;
    }

    public function getContractAddress(): ?string
    {
        return $this->contractAddress;
    }

    public function setType(string $type): GetDepositListsResponseDTO
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setBlockNumber(int $blockNumber): GetDepositListsResponseDTO
    {
        $this->blockNumber = $blockNumber;

        return $this;
    }

    public function getBlockNumber(): int
    {
        return $this->blockNumber;
    }

    public function setFrom(string $from): GetDepositListsResponseDTO
    {
        $this->from = $from;

        return $this;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function setTo(string $to): GetDepositListsResponseDTO
    {
        $this->to = $to;

        return $this;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function setGasPrice(float $gasPrice): GetDepositListsResponseDTO
    {
        $this->gasPrice = $gasPrice;

        return $this;
    }

    public function getGasPrice(): float
    {
        return $this->gasPrice;
    }

    public function setGasUsed(int $gasUsed): GetDepositListsResponseDTO
    {
        $this->gasUsed = $gasUsed;

        return $this;
    }

    public function getGasUsed(): int
    {
        return $this->gasUsed;
    }
}
