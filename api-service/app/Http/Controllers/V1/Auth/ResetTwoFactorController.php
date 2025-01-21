<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\ResetTwoFactor\DisableRequest;
use App\Http\Requests\V1\Auth\ResetTwoFactor\SendEmailVerificationRequest;
use App\Services\ResetTwoFactorService;

class ResetTwoFactorController extends Controller
{
    public function __construct(private readonly ResetTwoFactorService $resetTwoFactorService) {}

    /**
     * @OA\Post(
     *     path="/api/v1/2fa/reset/send-email-verification",
     *     summary="Send email verification to disable two-factor authentication",
     *     description="This endpoint sends an email verification code to the provided email address for disabling two-factor authentication.",
     *     tags={"Two-Factor Reset"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/SendEmailVerificationRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Email verification code has been sent successfully.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="A verification code has been sent to your email address to disable two-factor authentication."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="The given data was invalid."
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "email": {"The email field is required."}
     *                 }
     *             )
     *         )
     *     )
     * )
     */
    public function sendEmailVerification(SendEmailVerificationRequest $request)
    {
        $this->resetTwoFactorService->requestDisable(
            $request->input('email'),
            $request->ip()
        );

        return response([
            'message' => __('messages.created_succeed'),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/2fa/reset/disable",
     *     summary="Disable two-factor authentication using email and token",
     *     description="This endpoint disables two-factor authentication for a user after verifying the email and the token sent for verification.",
     *     tags={"Two-Factor Reset"},

     *
     *     @OA\Response(
     *         response=200,
     *         description="Two-factor authentication disabled successfully.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Two-factor authentication has been disabled successfully."
     *             )
     *         )
     *     ),
     *
     *          @OA\RequestBody(
     *          required=true,
     *
     *          @OA\JsonContent(ref="#/components/schemas/DisableRequest")
     *      ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="The given data was invalid."
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={
     *                     "token": {"The token field is required."},
     *                     "email": {"The email field is required."}
     *                 }
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Invalid token or email not associated with the account.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Invalid email or token."
     *             )
     *         )
     *     )
     * )
     */
    public function disable(DisableRequest $request)
    {
        $encryptedToken = $request->input('token');
        $email = $request->input('email');

        $this->resetTwoFactorService->disable(
            $email,
            $encryptedToken,
            $request->ip()
        );

        return response([
            'message' => __('auth.two-factor.disable-success'),
        ]);

    }
}
