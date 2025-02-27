<?php

namespace App\Services\User\DTO\FinancialBlock;

use Carbon\Carbon;

class GetUserBlockedStateResponseDTO
{
    private bool $isBlock;

    private Carbon $restrictUntil;

    public function setIsBlock(bool $isBlock): GetUserBlockedStateResponseDTO
    {
        $this->isBlock = $isBlock;

        return $this;
    }

    public function isBlock(): bool
    {
        return $this->isBlock;
    }

    public function setRestrictUntil(Carbon $restrictUntil): GetUserBlockedStateResponseDTO
    {
        $this->restrictUntil = $restrictUntil;

        return $this;
    }

    public function getRestrictUntil(): Carbon
    {
        return $this->restrictUntil;
    }
}
