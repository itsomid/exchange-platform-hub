<?php

namespace App\Repositories\Auth;

use App\Models\ReferralCode;

interface ReferralCodeRepositoryInterface
{
    public function getReferralCodeByCode(string $code): ReferralCode;
}
