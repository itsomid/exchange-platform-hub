<?php

namespace App\Services\User\DTO\ReferralCode;

use Carbon\Carbon;

class ReferralCodeGetListsResponseDTO
{
    private int $id;

    private string $code;

    private int $introducerFee;

    private int $friendFee;

    private int $totalFriendUsage;

    private int $totalCountTransaction;

    private string $totalAmountReceived;

    private Carbon $createdAt;

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setIntroducerFee(int $introducerFee): self
    {
        $this->introducerFee = $introducerFee;

        return $this;
    }

    public function getIntroducerFee(): int
    {
        return $this->introducerFee;
    }

    public function setFriendFee(int $friendFee): self
    {
        $this->friendFee = $friendFee;

        return $this;
    }

    public function getFriendFee(): int
    {
        return $this->friendFee;
    }

    public function setTotalFriendUsage(int $totalFriendUsage): self
    {
        $this->totalFriendUsage = $totalFriendUsage;

        return $this;
    }

    public function getTotalFriendUsage(): int
    {
        return $this->totalFriendUsage;
    }

    public function setTotalCountTransaction(int $totalCountTransaction): self
    {
        $this->totalCountTransaction = $totalCountTransaction;

        return $this;
    }

    public function getTotalCountTransaction(): int
    {
        return $this->totalCountTransaction;
    }

    public function setTotalAmountReceived(string $totalAmountReceived): self
    {
        $this->totalAmountReceived = $totalAmountReceived;

        return $this;
    }

    public function getTotalAmountReceived(): string
    {
        return $this->totalAmountReceived;
    }

    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    public function setCreatedAt(Carbon $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
