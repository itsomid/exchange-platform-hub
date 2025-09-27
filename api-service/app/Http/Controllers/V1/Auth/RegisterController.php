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


    public function resend()
    {
        $user = Auth::user();
        event(new Registered($user));

        return response([
            'message' => __('passwords.sent'),
        ]);
    }
}
