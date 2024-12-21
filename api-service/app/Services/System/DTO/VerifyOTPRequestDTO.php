<?php

namespace App\Services\System\DTO;

use App\Enums\EmailOTPActionEnum;

class VerifyOTPRequestDTO
{
    private string $email;

    private string $code;

    private EmailOTPActionEnum $action;

    public function setEmail(string $email): VerifyOTPRequestDTO
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setCode(string $code): VerifyOTPRequestDTO
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setAction(EmailOTPActionEnum $action): VerifyOTPRequestDTO
    {
        $this->action = $action;

        return $this;
    }

    public function getAction(): EmailOTPActionEnum
    {
        return $this->action;
    }
}
