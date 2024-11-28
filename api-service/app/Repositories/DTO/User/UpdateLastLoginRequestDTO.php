<?php

namespace App\Repositories\DTO\User;

use Carbon\Carbon;

class UpdateLastLoginRequestDTO
{
    private int $id;

    private string $ipAddress;

    private Carbon $lastLogin;

    public function setId(int $id): UpdateLastLoginRequestDTO
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
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

    public function setLastLogin(Carbon $lastLogin): self
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getLastLogin(): Carbon
    {
        return $this->lastLogin;
    }
}
