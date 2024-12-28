<?php

namespace App\Services\Profile;

use App\Exceptions\User\OldPasswordNotMatchedNewPasswordException;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Profile\DTO\UserActiveSessionsResponseDTO;
use App\Utils\LocationFinder;
use App\Utils\UserAgent;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

readonly class ProfileService
{
    public function __construct(private UserRepositoryInterface $userRepository) {}

    public function changePassword(ChangePasswordRequestDTO $requestDTO): void
    {
        $user = $this->userRepository->getUserById($requestDTO->getUserId());

        if (! Hash::check($requestDTO->getOldPassword(), $user->password)) {
            throw new OldPasswordNotMatchedNewPasswordException;
        }

        $this->userRepository->updateUser(
            $requestDTO->getUserId(),
            [
                'password' => Hash::make($requestDTO->getNewPassword()),
            ]
        );

        event(new PasswordReset($user));
    }

    public function updateProfile(UserUpdateProfileRequestDTO $requestDTO): void
    {
        $this->userRepository->updateUser($requestDTO->getUserId(), [
            'first_name' => $requestDTO->getFirstName(),
            'last_name' => $requestDTO->getLastName(),
            'mobile' => $requestDTO->getMobile(),
        ]);
    }

    public function getActiveSessions(int $userId): array
    {
        $user = $this->userRepository->getUserById($userId);

        return $user->tokens->map(function (PersonalAccessToken $token) {
            $userAgent = new UserAgent($token->user_agent);
            $locationFiner = new LocationFinder;

            return resolve(UserActiveSessionsResponseDTO::class)
                ->setLoginAt($token->created_at)
                ->setLocation($locationFiner->getCountryAndCity($token->ip))
                ->setPlatform($userAgent->getPlatform())
                ->setBrowser($userAgent->getBrowser())
                ->setIp($token->ip)
                ->setIsActive($token->expires_at->isPast());
        })->toArray();
    }
}
