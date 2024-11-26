<?php

namespace App\Repositories\Interfaces;

use App\Models\ReferralCode;
use App\Repositories\DTO\ReferralCode\ReferralCodeCreateDTO;
use Illuminate\Database\Eloquent\Collection;

interface ReferralCodeRepositoryInterface
{
    public function getReferralCodeByCode(string $code): ReferralCode;

    public function create(ReferralCodeCreateDTO $createDTO): ReferralCode;

    public function getByUserId(int $userId): Collection;
}
