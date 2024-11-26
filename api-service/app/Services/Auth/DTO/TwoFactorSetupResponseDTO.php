<?php

namespace App\Services\Auth\DTO;

class TwoFactorSetupResponseDTO
{
    private string $qrImage;

    private string $secretKey;

    public function setQrImage(string $qrImage): self
    {
        $this->qrImage = $qrImage;

        return $this;
    }

    public function getQrImage(): string
    {
        return $this->qrImage;
    }

    public function setSecretKey(string $secretKey): self
    {
        $this->secretKey = $secretKey;

        return $this;
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }
}
