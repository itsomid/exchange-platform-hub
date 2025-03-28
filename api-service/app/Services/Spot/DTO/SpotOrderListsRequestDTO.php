<?php

namespace App\Services\Spot\DTO;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;

class SpotOrderListsRequestDTO
{
    private int $userId;

    private ?SpotOrderSideEnum $side = null;

    private ?SpotOrderTypeEnum $type = null;

    private ?SpotOrderStatusEnum $status = null;

    public function setUserId(int $userId): SpotOrderListsRequestDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setSide(?string $side): SpotOrderListsRequestDTO
    {
        $this->side = SpotOrderSideEnum::tryFrom($side);

        return $this;
    }

    public function getSide(): ?SpotOrderSideEnum
    {
        return $this->side;
    }

    public function setType(?string $type): SpotOrderListsRequestDTO
    {
        $this->type = SpotOrderTypeEnum::tryFrom($type);

        return $this;
    }

    public function getType(): ?SpotOrderTypeEnum
    {
        return $this->type;
    }

    public function setStatus(?string $status): SpotOrderListsRequestDTO
    {
        $this->status = SpotOrderStatusEnum::tryFrom($status);

        return $this;
    }

    public function getStatus(): ?SpotOrderStatusEnum
    {
        return $this->status;
    }
}
