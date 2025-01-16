<?php

namespace App\Services\OTC\DTO\Order;

class OTCOrderListsRequestDTO
{
    private int $userId;

    private ?array $filterQueryString = null;

    public function setUserId(int $userId): OTCOrderListsRequestDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setFilterQueryString(?array $filterQueryString): OTCOrderListsRequestDTO
    {
        $this->filterQueryString = $filterQueryString;

        return $this;
    }

    public function getFilterQueryString(): ?array
    {
        return $this->filterQueryString;
    }
}
