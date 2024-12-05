<?php

namespace App\Services\Wallet\DTO\Wallet;

class GenerateAddressResponseDTO
{
    private string $address;

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }
}
