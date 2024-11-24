<?php

namespace App\Services\Auth;

use App\Exceptions\Auth\Google2faSecretInvalidException;
use App\Exceptions\Auth\InvalidUsernameOrPasswordException;
use App\Repositories\Auth\UpdateLastLoginRequestDTO;
use App\Repositories\Auth\UserRepositoryInterface;
use App\Services\Auth\DTO\CheckTwoFactorRequestDTO;
use App\Services\Auth\DTO\LoginRequestDTO;
use App\Services\Auth\DTO\LoginResponseDTO;
use Illuminate\Support\Facades\Hash;

readonly class LoginService
{
    public function __construct(private UserRepositoryInterface $userRepository) {}

    /**
     * @throws Google2faSecretInvalidException
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

        return resolve(LoginResponseDTO::class)
            ->setUser($user)
            ->setHasGoogle2fa(! is_null($user->google2fa_secret));
    }

    public function checkTwoFactor(CheckTwoFactorRequestDTO $checkTwoFactorRequestDTO): void
    {
        $user = $this->userRepository->getUserById($checkTwoFactorRequestDTO->getUserId());
        // check 2fa
        $google2fa = app('pragmarx.google2fa');
        $isValid = $google2fa->verifyKey($user->google2fa_secret, $checkTwoFactorRequestDTO->getGoogle2fa());

        if (! $isValid) {
            throw new Google2faSecretInvalidException;
        }
    }
}
