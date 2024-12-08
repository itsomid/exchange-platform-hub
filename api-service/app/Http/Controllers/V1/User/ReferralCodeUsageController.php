<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\User\ReferralCodeRegisteredUsersCollection;
use App\Http\Resources\V1\User\ReferralCodeTransactionsCollection;
use App\Services\User\DTO\ReferralCode\ReferralCodeGetOwnerProfitsRequestDTO;
use App\Services\User\DTO\ReferralCode\ReferralCodeGetRegisteredUsersRequestDTO;
use App\Services\User\ReferralCodeService;
use Illuminate\Support\Facades\Auth;

class ReferralCodeUsageController extends Controller
{
    public function __construct(private readonly ReferralCodeService $referralCodeService) {}

    /**
     * @OA\Get(
     *     path="/api/v1/referral-codes/referred-users/{referralCode}",
     *     summary="Get Registered Users by Referral Code",
     *     description="Retrieve the list of users registered using a specific referral code, including their total transaction count and the total profit generated.",
     *     operationId="getRegisteredUsers",
     *     tags={"Referral Code"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="referralCode",
     *         in="path",
     *         required=true,
     *         description="The referral code used to register users.",
     *
     *         @OA\Schema(type="string", example="REF74XGQ387")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of registered users retrieved successfully.",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/ReferralCodeRegisteredUsersResponse")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Referral Code not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="No results found.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="code does not belong to the user",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="error", type="string", example="The referral code does not belong to the user.")
     *         )
     *     )
     * )
     */
    public function registeredUsers(string $referralCode): ReferralCodeRegisteredUsersCollection
    {
        $registeredUsers = $this->referralCodeService->getRegisteredUsers(
            resolve(ReferralCodeGetRegisteredUsersRequestDTO::class)
                ->setUserId((int) Auth::id())
                ->setReferralCode($referralCode)
        );

        return new ReferralCodeRegisteredUsersCollection($registeredUsers);
    }

    public function ownerProfits(int $userId)
    {
        $transactionsDTO = $this->referralCodeService->getOwnerProfits(
            resolve(ReferralCodeGetOwnerProfitsRequestDTO::class)
                ->setUserId(Auth::id())
                ->setReferredUserId($userId)
        );

        return new ReferralCodeTransactionsCollection($transactionsDTO);
    }
}
