<?php

namespace App\Services\User\DTO\ReferralCode;

class ReferralCodeRegisteredUsersResponseDTO
{
    private int $userId;

    private string $userEmail;

    private string $userName;

    private int $totalOrders;

    private string $totalProfit;

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserEmail(string $userEmail): self
    {
        $this->userEmail = $userEmail;

        return $this;
    }

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    public function setUserName(string $userName): self
    {
        $this->userName = $userName;

        return $this;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function setTotalOrders(int $totalOrders): self
    {
        $this->totalOrders = $totalOrders;

        return $this;
    }

    public function getTotalOrders(): int
    {
        return $this->totalOrders;
    }

    public function setTotalProfit(string $totalProfit): self
    {
        $this->totalProfit = $totalProfit;

        return $this;
    }

    public function getTotalProfit(): string
    {
        return $this->totalProfit;
    }
}
