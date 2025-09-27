<?php

namespace App\Services\System;

use App\Exceptions\System\OTPRateLimitException;
use App\Mail\OTPDefaultMail;
use App\Repositories\DTO\System\EmailOTP\SaveNewEmailRequestDTO;
use App\Repositories\Interfaces\EmailOTPRepositoryInterface;
use App\Services\System\DTO\SendOTPRequestDTO;
use App\Services\System\DTO\VerifyOTPRequestDTO;
use App\Utils\RandomToken;
use Illuminate\Support\Facades\Mail;
use App\Enums\EmailOTPActionEnum;

class EmailOTPService
{
    const int CODE_LENGTH = 6;

    const int EXPIRATION_PER_MINUTES = 15;

    public function __construct(private EmailOTPRepositoryInterface $emailOTPRepository) {}

    public function send(SendOTPRequestDTO $requestDTO)
    {
        // Check if a recent OTP was sent within the last 60 seconds
        $lastToken = $this->emailOTPRepository->getLastToken(
            $requestDTO->getEmail(),
            $requestDTO->getAction()
        );

        if ($lastToken && now()->subSeconds(60)->lt($lastToken->created_at)) {
            throw new OTPRateLimitException();
        }

        // Delete all old OTP codes for this email and action before creating a new one
        $this->emailOTPRepository->deleteOldTokens(
            $requestDTO->getEmail(),
            $requestDTO->getAction()
        );

        $code = RandomToken::generate(self::CODE_LENGTH);

        $this->emailOTPRepository->saveNewEmail(
            resolve(SaveNewEmailRequestDTO::class)
                ->setEmail($requestDTO->getEmail())
                ->setCode($code)
                ->setAction($requestDTO->getAction())
        );

        $mailableClass = $requestDTO->getMailable() ?? OTPDefaultMail::class;

        Mail::to($requestDTO->getEmail())->send(
            new $mailableClass($code, $requestDTO->getName(), $requestDTO->getAction())
        );
    }

    public function verify(VerifyOTPRequestDTO $requestDTO): bool
    {
        $model = $this->emailOTPRepository->getLastToken(
            $requestDTO->getEmail(),
            $requestDTO->getAction()
        );

        if (is_null($model)) {
            return false;
        }

        $isValid = $model->code === $requestDTO->getCode()
            &&
            now()->subMinutes(self::EXPIRATION_PER_MINUTES)->lte($model->created_at);

        return $isValid;
    }

    public function deleteAllOTPCodesByAction(string $email, EmailOTPActionEnum $action): void
    {
        $this->emailOTPRepository->deleteOldTokens($email, $action);
    }
}
