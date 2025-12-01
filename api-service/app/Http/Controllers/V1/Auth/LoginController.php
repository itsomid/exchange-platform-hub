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
                    ->setIpAddress($request->ip())
                    ->setUserAgent($request->userAgent())
            );
            $data['token'] = new AccessTokenResource($tokenResponse);
        }

        return response([
            'message' => __('auth.login.success'),
            'data' => $data,
        ], Response::HTTP_OK);
    }
}
