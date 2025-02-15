<?php

namespace App\Services\Exchanges\DTO;

use App\Enums\SpotStatusEnum;

class ExchangeBuyResponseDTO
{
    private bool $isDone;

    private SpotStatusEnum $spotStatus;

    private int $errorCode;

    private ?string $errorMessage = null;

    public function setIsDone(bool $isDone): ExchangeBuyResponseDTO
    {
        $this->isDone = $isDone;

        return $this;
    }

    public function isDone(): bool
    {
        return $this->isDone;
    }

    public function setSpotStatus(SpotStatusEnum $spotStatus): ExchangeBuyResponseDTO
    {
        $this->spotStatus = $spotStatus;

        return $this;
    }

    public function getSpotStatus(): SpotStatusEnum
    {
        return $this->spotStatus;
    }

    public function setErrorCode(int $errorCode): ExchangeBuyResponseDTO
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    public function setErrorMessage(?string $errorMessage): ExchangeBuyResponseDTO
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}
