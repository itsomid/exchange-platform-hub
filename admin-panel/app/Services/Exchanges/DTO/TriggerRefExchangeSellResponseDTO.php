<?php

namespace App\Services\Exchanges\DTO;

class TriggerRefExchangeSellResponseDTO
{
    private bool $success;
    private string $message;

    public function setSuccess(bool $success): TriggerRefExchangeSellResponseDTO
    {
        $this->success = $success;
        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setMessage(string $message): TriggerRefExchangeSellResponseDTO
    {
        $this->message = $message;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
