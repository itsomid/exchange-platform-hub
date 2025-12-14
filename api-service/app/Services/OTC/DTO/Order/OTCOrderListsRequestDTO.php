<?php

namespace App\Services\OTC\DTO\Order;

class OTCOrderListsRequestDTO
{
    private int $userId;

    private ?array $filterQueryString = null;

    private int $page = 1;

    private int $limit = 10;

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

    public function setPage(int $page): OTCOrderListsRequestDTO
    {
        $this->page = $page;

        return $this;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setLimit(int $limit): OTCOrderListsRequestDTO
    {
        $this->limit = $limit;

        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
