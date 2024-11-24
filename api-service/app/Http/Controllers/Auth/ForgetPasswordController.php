<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgetRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ForgetPasswordController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/forgot-password",
     *     summary="Send password reset link to user's email",
     *     description="This endpoint accepts a user's email and sends a password reset link to that email address. The user can then use the link to reset their password.",
     *     operationId="sendResetPasswordEmail",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/ForgotPasswordRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Password reset link sent successfully to the user's email.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="We have emailed your password reset link!")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or invalid email address.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object", additionalProperties={"type": "array", "items": {"type": "string"}})
     *         )
     *     )
     * )
     */
    public function sendEmail(ForgetRequest $request): Response
    {
        Password::sendResetLink(
            $request->only('email')
        );

        return response([
            'message' => __('passwords.sent'),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/auth/reset-password",
     *     summary="Reset the user's password",
     *     description="This endpoint resets the user's password by providing a valid reset token, the user's email, and a new password. Upon success, the user's password will be updated, and the token will be invalidated.",
     *     operationId="resetPassword",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/ResetPasswordRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successfully.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Your password has been reset successfully.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Invalid or expired reset token.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="This password reset token is invalid or expired.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error with provided data.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object", additionalProperties={"type": "array", "items": {"type": "string"}})
     *         )
     *     )
     * )
     */
    public function resetPassword(ChangePasswordRequest $request): Response
    {
        $status = Password::reset(
            $request->only('email', 'password', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response([
                'message' => __('passwords.reset'),
            ])
            : response([
                'message' => __('passwords.token'),
            ], Response::HTTP_BAD_REQUEST);
    }
}
