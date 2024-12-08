<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\User\ReferralCodeRegisteredUsersCollection;
use App\Services\User\DTO\ReferralCode\ReferralCodeGetRegisteredUsersRequestDTO;
use App\Services\User\ReferralCodeService;
use Illuminate\Support\Facades\Auth;

class ReferralCodeUsageController extends Controller
{
    public function __construct(private readonly ReferralCodeService $referralCodeService) {}

    public function registeredUsers(string $referralCode)
    {
        $registeredUsers = $this->referralCodeService->getRegisteredUsers(
            resolve(ReferralCodeGetRegisteredUsersRequestDTO::class)
                ->setUserId((int) Auth::id())
                ->setReferralCode($referralCode)
        );

        return new ReferralCodeRegisteredUsersCollection($registeredUsers);
    }
}
