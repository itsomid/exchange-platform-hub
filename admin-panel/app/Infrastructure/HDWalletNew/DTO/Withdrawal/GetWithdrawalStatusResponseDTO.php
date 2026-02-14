<?php

namespace App\Infrastructure\HDWalletNew\DTO\Withdrawal;

class GetWithdrawalStatusResponseDTO
{
    private string $withdrawalId;
    private string $userId;
    private string $status;
    private string $network;
    private string $currencySymbol;
    private string $requestedAmount;
    private ?string $actualAmount = null;
    private ?string $fee = null;
    private string $toAddress;
    private ?string $txHash = null;
    private ?int $confirmations = null;
    private ?int $requiredConfirmations = null;
    private string $createdAt;
    private ?string $processedAt = null;
    private ?string $confirmedAt = null;
    private ?string $failureReason = null;
    private ?string $failedAt = null;

    public function getWithdrawalId(): string { return $this->withdrawalId; }
    public function setWithdrawalId(string $withdrawalId): self { $this->withdrawalId = $withdrawalId; return $this; }

    public function getUserId(): string { return $this->userId; }
    public function setUserId(string $userId): self { $this->userId = $userId; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getNetwork(): string { return $this->network; }
    public function setNetwork(string $network): self { $this->network = $network; return $this; }

    public function getCurrencySymbol(): string { return $this->currencySymbol; }
    public function setCurrencySymbol(string $currencySymbol): self { $this->currencySymbol = $currencySymbol; return $this; }

    public function getRequestedAmount(): string { return $this->requestedAmount; }
    public function setRequestedAmount(string $requestedAmount): self { $this->requestedAmount = $requestedAmount; return $this; }

    public function getActualAmount(): ?string { return $this->actualAmount; }
    public function setActualAmount(?string $actualAmount): self { $this->actualAmount = $actualAmount; return $this; }

    public function getFee(): ?string { return $this->fee; }
    public function setFee(?string $fee): self { $this->fee = $fee; return $this; }

    public function getToAddress(): string { return $this->toAddress; }
    public function setToAddress(string $toAddress): self { $this->toAddress = $toAddress; return $this; }

    public function getTxHash(): ?string { return $this->txHash; }
    public function setTxHash(?string $txHash): self { $this->txHash = $txHash; return $this; }

    public function getConfirmations(): ?int { return $this->confirmations; }
    public function setConfirmations(?int $confirmations): self { $this->confirmations = $confirmations; return $this; }

    public function getRequiredConfirmations(): ?int { return $this->requiredConfirmations; }
    public function setRequiredConfirmations(?int $requiredConfirmations): self { $this->requiredConfirmations = $requiredConfirmations; return $this; }

    public function getCreatedAt(): string { return $this->createdAt; }
    public function setCreatedAt(string $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getProcessedAt(): ?string { return $this->processedAt; }
    public function setProcessedAt(?string $processedAt): self { $this->processedAt = $processedAt; return $this; }

    public function getConfirmedAt(): ?string { return $this->confirmedAt; }
    public function setConfirmedAt(?string $confirmedAt): self { $this->confirmedAt = $confirmedAt; return $this; }

    public function getFailureReason(): ?string { return $this->failureReason; }
    public function setFailureReason(?string $failureReason): self { $this->failureReason = $failureReason; return $this; }

    public function getFailedAt(): ?string { return $this->failedAt; }
    public function setFailedAt(?string $failedAt): self { $this->failedAt = $failedAt; return $this; }
}
