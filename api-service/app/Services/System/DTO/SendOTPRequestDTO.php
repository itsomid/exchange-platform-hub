<?php

namespace App\Services\System\DTO;

use App\Enums\EmailOTPActionEnum;

class SendOTPRequestDTO
{
    private ?string $name = null;
    private string $email;

    private ?string $mailable = null;

    private EmailOTPActionEnum $action;

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setMailable(?string $mailable): self
    {
        $this->mailable = $mailable;

        return $this;
    }

    public function getMailable(): ?string
    {
        return $this->mailable;
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

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
