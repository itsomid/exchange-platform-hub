<?php

namespace App\Services\Spot\DTO;

use App\Models\SpotOrder;

class SpotTradeResponseDTO
{
    private ?SpotOrder $spotOrderModel = null;

    public function setSpotOrderModel(?SpotOrder $spotOrderModel): self
    {
        $this->spotOrderModel = $spotOrderModel;

        return $this;
    }

    public function getSpotOrderModel(): ?SpotOrder
    {
        return $this->spotOrderModel;
    }
}
