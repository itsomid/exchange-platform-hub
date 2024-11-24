<?php

namespace App\Services\Auth;

use App\Repositories\Auth\EmailVerificationSaveTokenRequestDTO;
use App\Repositories\Auth\ReferralCodeRepository;
use App\Repositories\Auth\UserEmailVerificationInterface;
use App\Repositories\Auth\UserRegisterRequestDTO;
use App\Repositories\Auth\UserRepositoryInterface;
use App\Services\Auth\DTO\RegisterRequestDTO;
use App\Services\Auth\DTO\RegisterResponseDTO;
use App\Utils\RandomToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

readonly class RegisterService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserEmailVerificationInterface $emailVerificationRepository,
        private ReferralCodeRepository $referralCodeRepository,
    ) {}

    //validated data
    public function register(RegisterRequestDTO $registerRequestDTO): RegisterResponseDTO
    {
        $referralCodeModel = null;
        if (! is_null($registerRequestDTO->getIntroducerCode())) {
            $referralCodeModel = $this->referralCodeRepository->getReferralCodeByCode($registerRequestDTO->getIntroducerCode());
        }

        //If user is exists and not verified then update all columns otherwise create new records
        $userCreatedModel = $this->userRepository->registerOrUpdate(
            resolve(UserRegisterRequestDTO::class)
                ->setUsername($this->generateUsername($registerRequestDTO->getEmail()))
                ->setEmail($registerRequestDTO->getEmail())
                ->setFirstName($registerRequestDTO->getFirstName())
                ->setLastName($registerRequestDTO->getLastName())
                ->setIntroducerId($referralCodeModel?->id)
                ->setHashedPassword(
                    Hash::make($registerRequestDTO->getPassword())
                )
        );

        //Generate random token
        $this->emailVerificationRepository->saveToken(
            resolve(EmailVerificationSaveTokenRequestDTO::class)
                ->setToken(
                    RandomToken::generate(
                        $registerRequestDTO->getLengthVerificationToken()
                    )
                )
                ->setUserId($userCreatedModel->id)
                ->setExpirationDate(
                    $registerRequestDTO->getTokenExpirationDate()
                )
        );

        return resolve(RegisterResponseDTO::class)
            ->setUser($userCreatedModel)
            ->setEmailVerificationActiveUntil(
                Carbon::now()->addMinutes(config('auth.verification.expire'))
            );
    }

    public function generateUsername(string $email): string
    {
        // Extract the part of the email before the '@'
        $baseUsername = Str::before($email, '@');

        // Clean up the base username: remove special characters, limit length
        $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', $baseUsername);
        $baseUsername = Str::limit($baseUsername, 20, '');

        // Start with the base username
        $username = $baseUsername;

        // Check for uniqueness
        $counter = 1;
        while ($this->userRepository->isUsernameExists($username)) {
            $username = $baseUsername.$counter; // Append a number if not unique
            $counter++;
        }

        return $username;
    }
}
