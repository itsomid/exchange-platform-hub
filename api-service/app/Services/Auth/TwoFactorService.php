<?php

namespace App\Services\Auth;

use App\Exceptions\Auth\Google2faSecretInvalidException;
use App\Exceptions\Auth\GoogleInvalidUserSecretKeyException;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Auth\DTO\CheckTwoFactorRequestDTO;
use App\Services\Auth\DTO\TwoFactorSaveSecretRequestDTO;
use App\Services\Auth\DTO\TwoFactorSetupRequestDTO;
use App\Services\Auth\DTO\TwoFactorSetupResponseDTO;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FAQRCode\Google2FA;

class TwoFactorService
{
    public function __construct(private UserRepositoryInterface $userRepository, private readonly Google2FA $google2faService) {}

    /**
     * @throws Google2faSecretInvalidException
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function checkTwoFactor(CheckTwoFactorRequestDTO $checkTwoFactorRequestDTO): void
    {
        $user = $this->userRepository->getUserById($checkTwoFactorRequestDTO->getUserId());

        // check 2fa
        $isValid = $this->google2faService->verifyKey($user->two_factor_secret, $checkTwoFactorRequestDTO->getGoogle2fa());

        if (! $isValid) {
            throw new Google2faSecretInvalidException;
        }
    }

    public function setup(TwoFactorSetupRequestDTO $requestDTO): TwoFactorSetupResponseDTO
    {
        $google2fa_secret = $this->google2faService->generateSecretKey();

        $QR_Image = $this->google2faService->getQRCodeInline(
            $requestDTO->getCompanyName(),
            $requestDTO->getEmail(),
            $google2fa_secret
        );

        return resolve(TwoFactorSetupResponseDTO::class)
            ->setSecretKey($google2fa_secret)
            ->setQRImage($QR_Image);
    }

    /**
     * @throws GoogleInvalidUserSecretKeyException
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function validateAndSaveSecret(TwoFactorSaveSecretRequestDTO $requestDTO): void
    {
        $is_valid = $this->google2faService->verifyKey($requestDTO->getGoogle2faSecret(), $requestDTO->getGoogle2fa());

        if (! $is_valid) {
            throw new GoogleInvalidUserSecretKeyException;
        }

        $this->userRepository->saveSecret($requestDTO->getUserId(), $requestDTO->getGoogle2faSecret());
    }
}
