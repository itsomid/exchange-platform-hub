<?php

namespace App\DTO\UserAccount;

use App\Enums\DepositStatusEnum;

class ChargeAccountRequestDTO
{
    private int $amount;

    private int $userId;

    private ?string $userDescription = null;

    private ?int $adminId = null;

    private DepositStatusEnum $depositType;

    /**
     * @return $this
     */
    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return $this
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return $this
     */
    public function setUserDescription(?string $userDescription): self
    {
        $this->userDescription = $userDescription ?? null; // Use empty string if null is passed

        return $this;
    }

    public function getUserDescription(): ?string
    {
        return $this->userDescription;
    }

    public function setSystemDescription(?string $systemDescription): self
    {
        $this->systemDescription = $systemDescription ?? null; // Use empty string if null is passed

        return $this;
    }

    public function getSystemDescription(): ?string
    {
        return $this->systemDescription;
    }

    /**
     * @return $this
     */
    /**
     * @return $this
     */
    public function setAdminId(?int $adminId): self
    {
        $this->adminId = $adminId;

        return $this;
    }

    public function getAdminId(): ?int
    {
        return $this->adminId;
    }

    public function getDepositType(): DepositStatusEnum
    {
        return $this->depositType;
    }

    /**
     * @return $this
     */
    public function setDepositType(DepositStatusEnum $depositType): self
    {
        $this->depositType = $depositType;

        return $this;
    }
}
