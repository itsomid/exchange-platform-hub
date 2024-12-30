<?php

namespace App\Services\Auth;

use App\Services\Auth\DTO\GenerateTokenRequestDTO;
use App\Services\Auth\DTO\GenerateTokenResponseDTO;
use App\Services\Auth\DTO\RevokeAllSessionRequestDTO;

class AccessTokenService
{
    /** Generate access token with Sanctum
     */
    public function generateToken(GenerateTokenRequestDTO $requestDTO): GenerateTokenResponseDTO
    {
        $user = $requestDTO->getUser();

        $tokenObject = $user->createToken(
            name: $requestDTO->getTokenName(),
            expiresAt: $requestDTO->getExpirationDate()
        );
        // Update token details with IP and user agent
        $tokenObject->accessToken->ip = $requestDTO->getIpAddress();
        $tokenObject->accessToken->user_agent = $requestDTO->getUserAgent();
        $tokenObject->accessToken->save();

        return resolve(GenerateTokenResponseDTO::class)
            ->setToken($tokenObject->plainTextToken);
    }

    /**
     * Revoke All Sessions
     */
    public function revokeAllSession(RevokeAllSessionRequestDTO $DTO): void
    {
        $userModel = $DTO->getUser();
        $userModel->tokens()->delete();
    }
}
