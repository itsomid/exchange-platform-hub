<?php

namespace App\Services\User\DTO\ReferralCode;

use App\Models\ReferralCode;

class ReferralCodeCreateResponseDTO
{
    private ReferralCode $referralModel;

    public function setReferralModel(ReferralCode $referralModel): self
    {
        $this->referralModel = $referralModel;

        return $this;
    }

    public function getReferralModel(): ReferralCode
    {
        return $this->referralModel;
    }
}
