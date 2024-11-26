<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\User\ReferralCodeCreateRequest;
use App\Http\Resources\V1\User\ReferralCodeListCollection;
use App\Http\Resources\V1\User\ReferralCodeResource;
use App\Services\User\DTO\ReferralCode\ReferralCodeCreateRequestDTO;
use App\Services\User\ReferralCodeService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ReferralCodeController extends Controller
{
    public function __construct(private readonly ReferralCodeService $referralCodeService) {}

    /**
     * @OA\Post(
     *     path="/api/v1/referral-codes",
     *     summary="Create Referral Code",
     *     description="Allows a user to create a referral code with specific friend fee and usage limits.",
     *     operationId="createReferralCode",
     *     tags={"Referral Code"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/ReferralCodeCreateRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Referral code created successfully.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Created successfully."),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/ReferralCodeResource")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object", additionalProperties={"type": "array", "items": {"type": "string"}})
     *         )
     *     )
     * )
     */
    public function store(ReferralCodeCreateRequest $request)
    {
        $validated = $request->validated();

        $responseDTO = $this->referralCodeService->create(
            resolve(ReferralCodeCreateRequestDTO::class)
                ->setUserId(Auth::id())
                ->setFriendFee($validated['friend_fee'])
                ->setMaxFee(config('user.referral-code.max-fee'))
                ->setUsageLimit(config('user.referral-code.usage-limit'))
        );

        return response([
            'message' => __('messages.created_succeed'),
            'data' => new ReferralCodeResource($responseDTO->getReferralModel()),
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/referral-codes",
     *     summary="List Referral Codes",
     *     description="Retrieve a list of referral codes created by the authenticated user.",
     *     operationId="listReferralCodes",
     *     tags={"Referral Code"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of referral codes retrieved successfully.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/ReferralCodeListResource")
     *             )
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
     *     )
     * )
     */
    public function lists()
    {
        $lists = $this->referralCodeService->lists(Auth::id());

        return response([
            'data' => new ReferralCodeListCollection($lists),
        ]);
    }
}
