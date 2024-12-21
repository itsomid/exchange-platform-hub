<?php

namespace App\Repositories\DTO\System\EmailOTP;

use App\Enums\EmailOTPActionEnum;
use App\Exceptions\EmailIsInvalidException;

class SaveNewEmailRequestDTO
{
    private string $email;

    private int $code;

    private EmailOTPActionEnum $action;

    /**
     * @throws EmailIsInvalidException
     */
    public function setEmail(string $email): self
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new EmailIsInvalidException;
        }
        $this->email = $email;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setCode(int $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function setAction(EmailOTPActionEnum $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getAction(): EmailOTPActionEnum
    {
        return $this->action;
    }
}
