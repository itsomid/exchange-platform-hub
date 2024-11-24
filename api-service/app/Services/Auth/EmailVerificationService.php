<?php

namespace App\Services\Auth;

use App\Exceptions\Auth\InvalidVerificationTokenException;
use App\Repositories\Auth\UserEmailVerificationInterface;
use App\Repositories\Auth\UserRepositoryInterface;
use App\Services\Auth\DTO\EmailVerifyRequestDTO;

readonly class EmailVerificationService
{
    public function __construct(private UserEmailVerificationInterface $emailVerificationRepository, private UserRepositoryInterface $userRepository) {}

    public function verify(EmailVerifyRequestDTO $DTO): void
    {
        $emailVerificationDTO = $this->emailVerificationRepository->getByToken($DTO->getToken());

        if (
            is_null($emailVerificationDTO) ||
            $emailVerificationDTO->getUserId() !== $DTO->getUserId() ||
            $emailVerificationDTO->getExpirationDate()->isPast()
        ) {
            throw new InvalidVerificationTokenException;
        }
        //Active Account
        $this->userRepository->activeAccount(
            $DTO->getUserId()
        );
    }
}
