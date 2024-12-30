<?php

namespace App\Services\OTC\DTO;

use App\Models\OTCOrder;

class OTCBuyResponseDTO
{
    private OTCOrder $otcOrderModel;

    public function setOtcOrderModel(OTCOrder $otcOrderModel): OTCBuyResponseDTO
    {
        $this->otcOrderModel = $otcOrderModel;

        return $this;
    }

    public function getOtcOrderModel(): OTCOrder
    {
        return $this->otcOrderModel;
    }
}
