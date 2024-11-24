<?php

namespace App\Repositories\Auth;

use App\Models\ReferralCode;

class ReferralCodeRepository implements ReferralCodeRepositoryInterface
{
    public function getReferralCodeByCode(string $code): ReferralCode
    {
        return ReferralCode::query()
            ->where('code', $code)
            ->first();
    }
}
