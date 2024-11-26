<?php

namespace App\Services\Profile;

use App\Exceptions\User\OldPasswordNotMatchedNewPasswordException;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;

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
}
