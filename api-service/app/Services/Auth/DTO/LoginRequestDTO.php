<?php

namespace App\Services\Auth\DTO;

use Carbon\Carbon;

class LoginRequestDTO
{
    private string $email;

    private string $password;

    private ?string $google2fa;

    private string $ipAddress;

    private Carbon $lastLogin;

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setGoogle2fa(?string $google2fa): self
    {
        $this->google2fa = $google2fa;

        return $this;
    }

    public function getGoogle2fa(): ?string
    {
        return $this->google2fa;
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
