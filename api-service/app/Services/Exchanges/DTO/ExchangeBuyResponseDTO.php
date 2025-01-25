<?php

namespace App\Services\Exchanges\DTO;

class ExchangeBuyResponseDTO
{
    private bool $isDone;

    public function setIsDone(bool $isDone): ExchangeBuyResponseDTO
    {
        $this->isDone = $isDone;

        return $this;
    }

    public function isDone(): bool
    {
        return $this->isDone;
    }
}
