<?php

namespace App\Services\Spot\DTO;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderTypeEnum;

class SpotTradeListsRequestDTO
{
    private int $userId;

    private ?SpotOrderSideEnum $side = null;

    private ?SpotOrderTypeEnum $type = null;

    public function setUserId(int $userId): SpotTradeListsRequestDTO
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setSide(?SpotOrderSideEnum $side): SpotTradeListsRequestDTO
    {
        $this->side = $side;

        return $this;
    }

    public function getSide(): ?SpotOrderSideEnum
    {
        return $this->side;
    }

    public function setType(?SpotOrderTypeEnum $type): SpotTradeListsRequestDTO
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ?SpotOrderTypeEnum
    {
        return $this->type;
    }
}
