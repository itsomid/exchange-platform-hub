<?php

namespace App\Services\User\DTO\FinancialBlock;

use App\Enums\FinancialBlockReasonsEnum;
use App\Enums\UserFinancialBlockAction;
use Carbon\Carbon;

class SaveFinancialBlockRequestDTO
{
    private int $userId;

    private UserFinancialBlockAction $action;

    private FinancialBlockReasonsEnum $reason;

    private Carbon $restrictedUntil;

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setAction(UserFinancialBlockAction $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getAction(): UserFinancialBlockAction
    {
        return $this->action;
    }

    public function setReason(FinancialBlockReasonsEnum $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function getReason(): FinancialBlockReasonsEnum
    {
        return $this->reason;
    }

    public function setRestrictedUntil(Carbon $restrictedUntil): self
    {
        $this->restrictedUntil = $restrictedUntil;

        return $this;
    }

    public function getRestrictedUntil(): Carbon
    {
        return $this->restrictedUntil;
    }
}
