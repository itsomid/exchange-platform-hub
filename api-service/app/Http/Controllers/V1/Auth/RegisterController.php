<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\RegisterRequest;
use App\Http\Resources\Auth\AccessTokenResource;
use App\Http\Resources\Auth\UserResource;
use App\Services\Auth\AccessTokenService;
use App\Services\Auth\DTO\GenerateTokenRequestDTO;
use App\Services\Auth\DTO\RegisterRequestDTO;
use App\Services\Auth\DTO\RevokeAllSessionRequestDTO;
use App\Services\Auth\RegisterService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function __construct(private readonly RegisterService $registerService, private readonly AccessTokenService $accessTokenService) {}

    /**
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     summary="Register a new user",
     *     description="Handles user registration, revokes previous sessions, generates a new access token, and sends a verification email.",
     *     operationId="registerUser",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/RegisterRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Registration successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Registration successful."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="token", ref="#/components/schemas/AccessTokenResource"),
     *                 @OA\Property(property="user", ref="#/components/schemas/UserResource")
     *             )
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
     *     ),
     *     security={
     *         {"throttle": {}}
     *     }
     * )
     */
    public function register(RegisterRequest $request): Response
    {
        $validatedData = $request->validated();
        // Create or update user's information
        $registerResponse = $this->registerService->register(
            resolve(RegisterRequestDTO::class)
                ->setEmail($validatedData['email'])
                ->setPassword($validatedData['password'])
                ->setIntroducerCode($validatedData['introducer_code'] ?? null)
                ->setLengthVerificationToken(5)
                ->setRegistrationDate(now())
                ->setTokenExpirationDate(
                    now()->addMinutes(
                        config('auth.verification.expire')
                    )
                )
        );

        // If already user has session then revoke all sessions
        $this->accessTokenService->revokeAllSession(
            resolve(RevokeAllSessionRequestDTO::class)
                ->setUser($registerResponse->getUser())
        );
        // Generate new access token
        $tokenResponse = $this->accessTokenService->generateToken(
            resolve(GenerateTokenRequestDTO::class)
                ->setUser($registerResponse->getUser())
                ->setTokenName('desktop')
                ->setIpAddress($request->ip())
                ->setUserAgent($request->userAgent())
        );
        //Send Email Activation
        event(new Registered($registerResponse->getUser()));

        return response([
            'message' => __('auth.register.success'),
            'data' => [
                'token' => new AccessTokenResource($tokenResponse),
                'user' => new UserResource($registerResponse->getUser()),
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/email/resend",
     *     summary="Resend Email Activation",
     *     description="Resend the activation email to the authenticated user.",
     *     operationId="resendEmailActivation",
     *     tags={"Authentication"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Activation email resent successfully.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="The activation email has been resent.")
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
     *         response=429,
     *         description="Too Many Requests",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Too many requests. Please try again later.")
     *         )
     *     )
     * )
     */
    public function resend()
    {
        $user = Auth::user();
        event(new Registered($user));

        return response([
            'message' => __('passwords.sent'),
        ]);
    }
}
