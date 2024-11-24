<?php

namespace App\Services\Auth\DTO;

use Carbon\Carbon;

class RegisterRequestDTO
{
    private string $firstName;

    private string $lastName;

    private string $email;

    private string $password;

    private ?string $introducerCode;

    private int $lengthVerificationToken;

    private Carbon $tokenExpirationDate;

    /**
     * @return $this
     */
    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @return $this
     */
    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

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
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setLengthVerificationToken(int $lengthVerificationToken): self
    {
        $this->lengthVerificationToken = $lengthVerificationToken;

        return $this;
    }

    public function getLengthVerificationToken(): int
    {
        return $this->lengthVerificationToken;
    }

    public function setTokenExpirationDate(Carbon $tokenExpirationDate): RegisterRequestDTO
    {
        $this->tokenExpirationDate = $tokenExpirationDate;

        return $this;
    }

    public function getTokenExpirationDate(): Carbon
    {
        return $this->tokenExpirationDate;
    }

    public function setIntroducerCode(?string $introducerCode): self
    {
        $this->introducerCode = $introducerCode;

        return $this;
    }

    public function getIntroducerCode(): ?string
    {
        return $this->introducerCode;
    }
}
