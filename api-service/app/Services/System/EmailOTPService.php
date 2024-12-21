<?php

namespace App\Services\System;

use App\Mail\OTPDefaultMail;
use App\Repositories\DTO\System\EmailOTP\SaveNewEmailRequestDTO;
use App\Repositories\Interfaces\EmailOTPRepositoryInterface;
use App\Services\System\DTO\SendOTPRequestDTO;
use App\Services\System\DTO\VerifyOTPRequestDTO;
use App\Utils\RandomToken;
use Illuminate\Support\Facades\Mail;

class EmailOTPService
{
    const int CODE_LENGTH = 6;
    const int EXPIRATION_PER_MINUTES = 15;

    public function __construct(private EmailOTPRepositoryInterface $emailOTPRepository) {}

    public function send(SendOTPRequestDTO $requestDTO)
    {
        $code = RandomToken::generate(self::CODE_LENGTH);

        $this->emailOTPRepository->saveNewEmail(
            resolve(SaveNewEmailRequestDTO::class)
                ->setEmail($requestDTO->getEmail())
                ->setCode($code)
                ->setAction($requestDTO->getAction())
        );

        $mailableClass = $requestDTO->getMailable() ?? OTPDefaultMail::class;

        Mail::to($requestDTO->getEmail())->send(
            new $mailableClass($code, $requestDTO->getName())
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

        return
            $model->code === $requestDTO->getCode()
            &&
            now()->subMinutes(self::EXPIRATION_PER_MINUTES)->lte($model->created_at);
    }
}
