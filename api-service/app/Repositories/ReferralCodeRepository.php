<?php

namespace App\Repositories;

use App\Models\ReferralCode;
use App\Repositories\DTO\ReferralCode\ReferralCodeCreateDTO;
use App\Repositories\Interfaces\ReferralCodeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ReferralCodeRepository implements ReferralCodeRepositoryInterface
{
    public function getReferralCodeByCode(string $code): ReferralCode
    {
        return ReferralCode::query()
            ->where('code', $code)
            ->first();
    }

    public function create(ReferralCodeCreateDTO $createDTO): ReferralCode
    {
        return ReferralCode::query()
            ->create([
                'code' => $createDTO->getCode(),
                'friend_fee' => $createDTO->getFriendFee(),
                'introducer_fee' => $createDTO->getIntroducerFee(),
                'usage_limit' => $createDTO->getUsageLimit(),
                'user_id' => $createDTO->getUserId(),
            ]);
    }

    public function getByUserId(int $userId): Collection
    {
        return ReferralCode::query()
            ->withCount('registeredUsers')
            ->withCount('referralCodeUsage')
            ->withSum('transactions', 'amount')
            ->where('user_id', $userId)
//            ->select('code', 'introducer_fee', 'friend_fee', 'created_at', 'usage_limit')
            ->get();
    }
}
