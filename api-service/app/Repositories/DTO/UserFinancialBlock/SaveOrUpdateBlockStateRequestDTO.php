<?php

namespace App\Repositories\DTO\UserFinancialBlock;

use App\Enums\FinancialBlockActionEnum;
use App\Enums\FinancialBlockReasonsEnum;
use Carbon\Carbon;

class SaveOrUpdateBlockStateRequestDTO
{
    private int $userId;

    private FinancialBlockActionEnum $action;

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

    public function setAction(FinancialBlockActionEnum $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getAction(): FinancialBlockActionEnum
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
