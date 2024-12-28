<?php

namespace App\Services\Profile\DTO;

use Carbon\Carbon;

class UserActiveSessionsResponseDTO
{
    private Carbon $loginAt;

    private string $location;

    private string $browser;

    private string $platform;

    private string $ip;

    private bool $isActive;

    public function setLoginAt(Carbon $loginAt): UserActiveSessionsResponseDTO
    {
        $this->loginAt = $loginAt;

        return $this;
    }

    public function getLoginAt(): Carbon
    {
        return $this->loginAt;
    }

    public function setLocation(string $location): UserActiveSessionsResponseDTO
    {
        $this->location = $location;

        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setBrowser(string $browser): UserActiveSessionsResponseDTO
    {
        $this->browser = $browser;

        return $this;
    }

    public function getBrowser(): string
    {
        return $this->browser;
    }

    public function setIp(string $ip): UserActiveSessionsResponseDTO
    {
        $this->ip = $ip;

        return $this;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setIsActive(bool $isActive): UserActiveSessionsResponseDTO
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setPlatform(string $platform): UserActiveSessionsResponseDTO
    {
        $this->platform = $platform;

        return $this;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }
}
