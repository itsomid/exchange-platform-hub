<?php

namespace App\Http\Controllers\V1\Wallet;

use App\Enums\WithdrawalStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Wallet\WithdrawRequest;
use App\Http\Resources\V1\Wallet\CheckWithdrawalResource;
use App\Http\Resources\V1\Wallet\WithdrawalResource;
use App\Services\Wallet\DTO\Withdrawal\CreateWithdrawalRequestDTO;
use App\Services\Wallet\WithdrawalService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WithdrawController extends Controller
{
    public function __construct(private readonly WithdrawalService $service) {}

    /**
     * @OA\Post(
     *     path="/api/v1/wallets/withdrawal",
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

    /**
     * @OA\Post(
     *     path="/api/v1/wallets/check-withdrawal",
     *     summary="Check the status of a withdrawal",
     *     description="This endpoint checks the status of a withdrawal from the HD wallet. The status can be pending, completed, or failed.",
     *     operationId="checkWithdrawal",
     *     tags={"Wallet"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Withdrawal status checked successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 description="The message indicating withdrawal status",
     *                 example="Your withdrawal request has been successfully processed"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="available_in",
     *                     type="string",
     *                     format="date-time",
     *                     description="The time when the next withdrawal check will be available",
     *                     example="2025-02-19 15:30:00"
     *                 ),
     *                 @OA\Property(
     *                     property="has_new_transaction",
     *                     type="integer",
     *                     description="Indicates if a new transaction has been completed (1 = yes, 0 = no)",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="withdrawal_details",
     *                     ref="#/components/schemas/CheckWithdrawalResource",
     *                     nullable=true
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Unauthorized"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Internal Server Error"
     *             )
     *         )
     *     )
     * )
     */
    public function checkWithdrawal()
    {
        $service = resolve(WithdrawalService::class);
        $response = $service->checkWithdrawal(Auth::id());
        Log::channel('hd-wallet')->info("javab: ".$response->getStatus());
        $message = __('messages.withdrawals.check_withdrawal_started');
        if ($response->getStatus() === WithdrawalStatusEnum::COMPLETED) {
            $message = __('messages.withdrawals.check_withdrawal_success');
        } elseif ($response->getStatus() === WithdrawalStatusEnum::FAILED) {
            $message = __('messages.withdrawals.check_withdrawal_error');
        }

        $data = [
            'available_in' => now()->addMinutes(config('bitexroom.withdrawal.check_wallet_attempts'))->format('Y-m-d H:i:s'),
            'has_new_transaction' => $response->getStatus() === WithdrawalStatusEnum::COMPLETED,
        ];
        if ($response->getStatus() === WithdrawalStatusEnum::COMPLETED) {
            $data['withdrawal_details'] = new CheckWithdrawalResource($response);
        }

        return response([
            'message' => $message,
            'data' => $data,
        ]);
    }
}
