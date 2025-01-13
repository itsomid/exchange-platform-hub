<?php

namespace App\Services\OTC\DTO\Order;

class OTCOrderListsRequestDTO
{
    private int $userId;

    public function setUserId(int $userId): OTCOrderListsRequestDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
