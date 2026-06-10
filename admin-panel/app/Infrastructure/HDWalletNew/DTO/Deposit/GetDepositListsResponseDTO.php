<?php

namespace App\Infrastructure\HDWalletNew\DTO\Deposit;

class GetDepositListsResponseDTO
{
    private string $depositId;
    private string $txHash;
    private string $network;
    private string $currencySymbol;
    private ?string $userId = null;
    private string $amount;
    private string $status;
    private ?int $confirmations = null;
    private ?int $requiredConfirmations = null;
    private string $toAddress;
    private ?string $fromAddress = null;
    private ?int $blockNumber = null;
    private ?string $contractAddress = null;
    private bool $isCredited;
    private ?string $creditedAt = null;
    private string $createdAt;

    public function getDepositId(): string { return $this->depositId; }
    public function setDepositId(string $depositId): self { $this->depositId = $depositId; return $this; }

    public function getTxHash(): string { return $this->txHash; }
    public function setTxHash(string $txHash): self { $this->txHash = $txHash; return $this; }

    public function getNetwork(): string { return $this->network; }
    public function setNetwork(string $network): self { $this->network = $network; return $this; }

    public function getCurrencySymbol(): string { return $this->currencySymbol; }
    public function setCurrencySymbol(string $currencySymbol): self { $this->currencySymbol = $currencySymbol; return $this; }

    public function getUserId(): ?string { return $this->userId; }
    public function setUserId(?string $userId): self { $this->userId = $userId; return $this; }

    public function getAmount(): string { return $this->amount; }
    public function setAmount(string $amount): self { $this->amount = $amount; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getConfirmations(): ?int { return $this->confirmations; }
    public function setConfirmations(?int $confirmations): self { $this->confirmations = $confirmations; return $this; }

    public function getRequiredConfirmations(): ?int { return $this->requiredConfirmations; }
    public function setRequiredConfirmations(?int $requiredConfirmations): self { $this->requiredConfirmations = $requiredConfirmations; return $this; }

    public function getToAddress(): string { return $this->toAddress; }
    public function setToAddress(string $toAddress): self { $this->toAddress = $toAddress; return $this; }

    public function getFromAddress(): ?string { return $this->fromAddress; }
    public function setFromAddress(?string $fromAddress): self { $this->fromAddress = $fromAddress; return $this; }

    public function getBlockNumber(): ?int { return $this->blockNumber; }
    public function setBlockNumber(?int $blockNumber): self { $this->blockNumber = $blockNumber; return $this; }

    public function getContractAddress(): ?string { return $this->contractAddress; }
    public function setContractAddress(?string $contractAddress): self { $this->contractAddress = $contractAddress; return $this; }

    public function getIsCredited(): bool { return $this->isCredited; }
    public function setIsCredited(bool $isCredited): self { $this->isCredited = $isCredited; return $this; }

    public function getCreditedAt(): ?string { return $this->creditedAt; }
    public function setCreditedAt(?string $creditedAt): self { $this->creditedAt = $creditedAt; return $this; }

    public function getCreatedAt(): string { return $this->createdAt; }
    public function setCreatedAt(string $createdAt): self { $this->createdAt = $createdAt; return $this; }

    // ──────────────────────────────────────────────
    //  Compatibility methods with old system DTOs
    // ──────────────────────────────────────────────

    public function getTransactionHash(): string { return $this->txHash; }
    public function getCryptocurrency(): string { return $this->currencySymbol; }
    public function getWalletAddress(): string { return $this->toAddress; }
    public function getFrom(): ?string { return $this->fromAddress; }
    public function getBlockChain(): string { return strtoupper($this->network); }
    public function getConfirmationBlocks(): ?int { return $this->confirmations; }

    /**
     * Get timestamp as Carbon instance for compatibility with old system.
     * Prefers creditedAt (actual credit time) over createdAt when available.
     */
    public function getTimestamp(): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse($this->creditedAt ?? $this->createdAt);
    }
}
