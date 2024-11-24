<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailVerificationRequest;
use App\Services\Auth\DTO\EmailVerifyRequestDTO;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    public function __construct(private readonly EmailVerificationService $emailVerificationService) {}

    /**
     * @OA\Post(
     *     path="/auth/email-verification",
     *     summary="Verify email address and activate account",
     *     description="This endpoint verifies the user's email address using a provided verification token. If the token is valid, the user's account is activated, and a success message is returned. No access token is returned in this case.",
     *     operationId="verifyEmail",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/EmailVerificationRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Email verified successfully and account activated.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Email verified successfully.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or invalid token.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object", additionalProperties={"type": "array", "items": {"type": "string"}})
     *         )
     *     ),
     *     security={
     *         {"sanctum": {}}
     *     },
     *
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *
     *         @OA\Schema(type="string", example="Bearer {access_token}")
     *     )
     * )
     */
    public function __invoke(EmailVerificationRequest $request)
    {
        $validatedData = $request->validated();

        $this->emailVerificationService->verify(
            resolve(EmailVerifyRequestDTO::class)
                ->setUserId(Auth::id())
                ->setToken($validatedData['token'])
        );

        return response([
            'message' => __('auth.email-verification.success'),
        ]);
    }
}
