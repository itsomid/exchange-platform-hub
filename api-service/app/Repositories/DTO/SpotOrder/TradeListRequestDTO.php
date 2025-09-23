<?php

namespace App\Repositories\DTO\SpotOrder;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;

class TradeListRequestDTO
{
    private int $userId;

    private ?SpotOrderSideEnum $side = null;

    private ?SpotOrderTypeEnum $type = null;

    private ?SpotOrderStatusEnum $status = null;

    private ?int $marketId = null;

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setSide(?SpotOrderSideEnum $side): self
    {
        $this->side = $side;

        return $this;
    }

    public function getSide(): ?SpotOrderSideEnum
    {
        return $this->side;
    }

    public function setType(?SpotOrderTypeEnum $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ?SpotOrderTypeEnum
    {
        return $this->type;
    }

    public function setStatus(?SpotOrderStatusEnum $status): TradeListRequestDTO
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): ?SpotOrderStatusEnum
    {
        return $this->status;
    }

    public function setMarketId(?int $marketId): self
    {
        $this->marketId = $marketId;

        return $this;
    }

    public function getMarketId(): ?int
    {
        return $this->marketId;
    }
}
