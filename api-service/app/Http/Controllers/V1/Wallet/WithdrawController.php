<?php

namespace App\Http\Controllers\V1\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Wallet\WithdrawRequest;
use App\Http\Resources\V1\Wallet\WithdrawalResource;
use App\Services\Wallet\DTO\Withdrawal\CreateWithdrawalRequestDTO;
use App\Services\Wallet\WithdrawalService;
use Illuminate\Support\Facades\Auth;

class WithdrawController extends Controller
{
    public function __construct(private readonly WithdrawalService $service) {}

    /**
     * @OA\Post(
     *     path="/api/v1/wallets/withdraw",
     *     summary="Withdraw Funds",
     *     description="Initiate a withdrawal request for a specific currency and chain.",
     *     tags={"Wallet"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/WithdrawRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Withdrawal request created successfully.",
     *
     *         @OA\JsonContent(ref="#/components/schemas/WithdrawalResource")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed for the request.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="The given data was invalid."
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="currency",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The selected currency is invalid.")
     *                 ),
     *
     *                 @OA\Property(
     *                     property="currency_chain",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The selected currency chain is invalid.")
     *                 ),
     *
     *                 @OA\Property(
     *                     property="destination_address",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The destination address is not valid.")
     *                 ),
     *
     *                 @OA\Property(
     *                 property="2fa_code",
     *                 type="array",
     *
     *                 @OA\Items(type="string", example="The Google 2FA code provided is invalid.")
     *                 ),
     *
     *                 @OA\Property(
     *                     property="otp_code",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The OTP code is invalid.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function __invoke(WithdrawRequest $request)
    {
        $validatedData = $request->validated();
        $withdrawResponse = $this->service->createWithdrawal(
            resolve(CreateWithdrawalRequestDTO::class)
                ->setUserId(Auth::id())
                ->setCurrencySymbol($validatedData['currency'])
                ->setCurrencyChain($validatedData['currency_chain'])
                ->setAmount($validatedData['amount'])
                ->setAddress($validatedData['destination_address'])
        );

        return new WithdrawalResource($withdrawResponse);
    }
}
