<?php

namespace App\Infrastructure\HDWalletNew\DTO\Address;

class GenerateAddressResponseDTO
{
    private string $userId;
    private string $network;
    private string $currencySymbol;
    private string $address;
    private int $addressIndex;
    private string $derivationPath;
    private bool $isActive;
    private int $depositCount;
    private string $totalDeposited;
    private ?string $lastDepositAt;
    private string $createdAt;

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getNetwork(): string
    {
        return $this->network;
    }

    public function setNetwork(string $network): self
    {
        $this->network = $network;
        return $this;
    }

    public function getCurrencySymbol(): string
    {
        return $this->currencySymbol;
    }

    public function setCurrencySymbol(string $currencySymbol): self
    {
        $this->currencySymbol = $currencySymbol;
        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getAddressIndex(): int
    {
        return $this->addressIndex;
    }

    public function setAddressIndex(int $addressIndex): self
    {
        $this->addressIndex = $addressIndex;
        return $this;
    }

    public function getDerivationPath(): string
    {
        return $this->derivationPath;
    }

    public function setDerivationPath(string $derivationPath): self
    {
        $this->derivationPath = $derivationPath;
        return $this;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getDepositCount(): int
    {
        return $this->depositCount;
    }

    public function setDepositCount(int $depositCount): self
    {
        $this->depositCount = $depositCount;
        return $this;
    }

    public function getTotalDeposited(): string
    {
        return $this->totalDeposited;
    }

    public function setTotalDeposited(string $totalDeposited): self
    {
        $this->totalDeposited = $totalDeposited;
        return $this;
    }

    public function getLastDepositAt(): ?string
    {
        return $this->lastDepositAt;
    }

    public function setLastDepositAt(?string $lastDepositAt): self
    {
        $this->lastDepositAt = $lastDepositAt;
        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
