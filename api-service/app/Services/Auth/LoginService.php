<?php

namespace App\Services\Auth;

use App\Exceptions\Auth\InvalidUsernameOrPasswordException;
use App\Repositories\DTO\User\UpdateLastLoginRequestDTO;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Auth\DTO\LoginRequestDTO;
use App\Services\Auth\DTO\LoginResponseDTO;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

readonly class LoginService
{
    public function __construct(private UserRepositoryInterface $userRepository) {}

    /**
     * @throws InvalidUsernameOrPasswordException
     */
    public function login(LoginRequestDTO $loginRequestDTO): LoginResponseDTO
    {
        $user = $this->userRepository->getUserByEmail($loginRequestDTO->getEmail());

        if (! Hash::check($loginRequestDTO->getPassword(), $user->password)) {
            throw new InvalidUsernameOrPasswordException;
        }

        //Update last login
        $this->userRepository->updateLastLogin(
            resolve(UpdateLastLoginRequestDTO::class)
                ->setLastLogin($loginRequestDTO->getLastLogin())
                ->setIpAddress($loginRequestDTO->getIpAddress())
                ->setId($user->id)
        );

        $response = resolve(LoginResponseDTO::class)
            ->setUser($user)
            ->setHasGoogle2fa(false);
        if (! empty($user->two_factor_secret)) {
            $response->setEncryptedToken($this->generateEncryptedToken($user->id))
                ->setHasGoogle2fa(true);
        }

        return $response;
    }

    public function generateEncryptedToken(int $userId): string
    {
        $token = Crypt::encryptString((string) $userId);
        Cache::put($token,
            true,
            now()->addMinutes(10));

        return $token;
    }

    public function getDecryptedToken(string $token): ?int
    {
        if (! Cache::has($token)) {
            return null;
        }

        Cache::delete($token);

        return (int) Crypt::decryptString($token);
    }
}
