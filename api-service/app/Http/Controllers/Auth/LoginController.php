<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\ValidateTwoFactorRequest;
use App\Http\Resources\Auth\AccessTokenResource;
use App\Http\Resources\Auth\UserResource;
use App\Services\Auth\AccessTokenService;
use App\Services\Auth\DTO\CheckTwoFactorRequestDTO;
use App\Services\Auth\DTO\GenerateTokenRequestDTO;
use App\Services\Auth\DTO\LoginRequestDTO;
use App\Services\Auth\LoginService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __construct(private readonly LoginService $loginService, private readonly AccessTokenService $accessTokenService) {}

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="Log in a user",
     *     description="Authenticates a user and optionally generates an access token. If the user has two-factor authentication enabled, the access token is not returned, and additional verification is required.",
     *     operationId="loginUser",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/LoginRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Login successful."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user", ref="#/components/schemas/UserResource"),
     *                 @OA\Property(property="token", ref="#/components/schemas/AccessTokenResource", nullable=true)
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
    public function login(LoginRequest $request)
    {
        $validatedData = $request->validated();

        $responseDTO = $this->loginService->login(
            resolve(LoginRequestDTO::class)
                ->setEmail($validatedData['email'])
                ->setPassword($validatedData['password'])
                ->setLastLogin(now())
                ->setIpAddress($request->ip())
                ->setGoogle2fa($validatedData['google2fa'] ?? null)
        );

        // Generate new access token
        $tokenResponse = $this->accessTokenService->generateToken(
            resolve(GenerateTokenRequestDTO::class)
                ->setUser($responseDTO->getUser())
                ->setTokenName('desktop')
                ->setExpirationDate(now()->addMinutes(60)) // 1 hour
        );

        $data = [
            'user' => new UserResource($responseDTO->getUser()),
        ];
        if (! $responseDTO->getHasGoogle2fa()) {
            $data['token'] = new AccessTokenResource($tokenResponse);
        }

        return response([
            'message' => __('auth.login.success'),
            'data' => $data,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/auth/two-factor/validate",
     *     summary="Validate two-factor authentication code and generate access token",
     *     description="This endpoint accepts a two-factor authentication (2FA) code from the user and validates it. If the code is correct, it generates and returns an access token that can be used to authenticate further requests. The API will reject any invalid or missing 2FA code with an error.",
     *     operationId="validateTwoFactor",
     *     tags={"Authentication"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/ValidateTwoFactorRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Two-factor validation successful",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Login successful."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="token", ref="#/components/schemas/AccessTokenResource")
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
     *         {"sanctum": {}}
     *     }
     * )
     */
    public function validateTwoFactor(ValidateTwoFactorRequest $request): Response
    {
        $validatedData = $request->validated();

        $this->loginService->checkTwoFactor(
            resolve(CheckTwoFactorRequestDTO::class)
                ->setGoogle2fa($validatedData['google2fa'])
                ->setUserId(Auth::id())
        );

        // Generate new access token
        $tokenResponse = $this->accessTokenService->generateToken(
            resolve(GenerateTokenRequestDTO::class)
                ->setUser(Auth::user())
                ->setTokenName('desktop')
                ->setExpirationDate(now()->addMinutes(60)) // 1 hour
        );

        return response([
            'message' => __('auth.login.success'),
            'data' => [
                'token' => new AccessTokenResource($tokenResponse),
            ],
        ], Response::HTTP_OK);
    }
}
