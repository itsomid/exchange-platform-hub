<?php

namespace App\Services\Auth\DTO;

use App\Models\User;
use Carbon\Carbon;

class GenerateTokenRequestDTO
{
    private User $user;

    private string $tokenName;
    private string $ipAddress;
    private string $userAgent;

    private Carbon $expirationDate;

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /** Token name could set device name such as desktop or mobile
     */
    public function setTokenName(string $tokenName): GenerateTokenRequestDTO
    {
        $this->tokenName = $tokenName;

        return $this;
    }

    /**
     * Token name could set device name such as desktop or mobile
     */
    public function getTokenName(): string
    {
        return $this->tokenName;
    }

    public function setExpirationDate(Carbon $expirationDate): GenerateTokenRequestDTO
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    public function getExpirationDate(): Carbon
    {
        return $this->expirationDate;
    }

    public function setIpAddress(string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function setUserAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }
}
