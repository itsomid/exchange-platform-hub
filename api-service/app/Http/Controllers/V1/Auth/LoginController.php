<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Http\Resources\Auth\AccessTokenResource;
use App\Http\Resources\Auth\UserResource;
use App\Services\Auth\AccessTokenService;
use App\Services\Auth\DTO\GenerateTokenRequestDTO;
use App\Services\Auth\DTO\LoginRequestDTO;
use App\Services\Auth\LoginService;
use Illuminate\Http\Response;

class LoginController extends Controller
{
    public function __construct(private readonly LoginService $loginService, private readonly AccessTokenService $accessTokenService) {}

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
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

        $data = [
            'user' => new UserResource($responseDTO->getUser(), $responseDTO->getEncryptedToken()),
        ];
        if (! $responseDTO->getHasGoogle2fa()) {
            // Generate new access token
            $tokenResponse = $this->accessTokenService->generateToken(
                resolve(GenerateTokenRequestDTO::class)
                    ->setUser($responseDTO->getUser())
                    ->setTokenName('desktop')
                    ->setExpirationDate(now()->addMinutes(60)) // 1 hour
            );
            $data['token'] = new AccessTokenResource($tokenResponse);
        }

        return response([
            'message' => __('auth.login.success'),
            'data' => $data,
        ], Response::HTTP_OK);
    }
}
