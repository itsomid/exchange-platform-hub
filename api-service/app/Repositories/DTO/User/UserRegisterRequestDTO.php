<?php

namespace App\Repositories\DTO\User;

use App\Enums\UserStatusEnum;
use Carbon\Carbon;

class UserRegisterRequestDTO
{
    private string $email;

    private string $username;

    private ?int $introducerId;

    private string $hashedPassword;

    private UserStatusEnum $userStatus;
    
    private Carbon $registrationDate;

    /**
     * @return $this
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return $this
     */
    public function setHashedPassword(string $hashedPassword): self
    {
        $this->hashedPassword = $hashedPassword;

        return $this;
    }

    public function getHashedPassword(): string
    {
        return $this->hashedPassword;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setIntroducerId(?int $introducerId): self
    {
        $this->introducerId = $introducerId;

        return $this;
    }

    public function getIntroducerId(): ?int
    {
        return $this->introducerId;
    }

    public function setUserStatus(UserStatusEnum $userStatus): UserRegisterRequestDTO
    {
        $this->userStatus = $userStatus;

        return $this;
    }

    public function getUserStatus(): UserStatusEnum
    {
        return $this->userStatus;
    }

    public function setRegistrationDate(Carbon $registrationDate): self
    {
        $this->registrationDate = $registrationDate;

        return $this;
    }

    public function getRegistrationDate(): Carbon
    {
        return $this->registrationDate;
    }
}
