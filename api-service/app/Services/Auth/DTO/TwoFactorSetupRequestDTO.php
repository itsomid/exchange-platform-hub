<?php

namespace App\Services\Auth\DTO;

class TwoFactorSetupRequestDTO
{
    private string $email;

    private string $companyName;

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setCompanyName(string $companyName): self
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }
}
