<?php

namespace App\Services\OTC\DTO;

use App\Models\OTCOrder;

class OTCSellResponseDTO
{
    private OTCOrder $otcOrderModel;

    public function setOtcOrderModel(OTCOrder $otcOrderModel): OTCSellResponseDTO
    {
        $this->otcOrderModel = $otcOrderModel;

        return $this;
    }

    public function getOtcOrderModel(): OTCOrder
    {
        return $this->otcOrderModel;
    }
}
