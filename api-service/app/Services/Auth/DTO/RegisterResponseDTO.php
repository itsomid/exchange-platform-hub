<?php

namespace App\Services\Auth\DTO;

use App\Models\User;
use Carbon\Carbon;

class RegisterResponseDTO
{
    private Carbon $emailVerificationActiveUntil;

    private User $user;

    /**
     * @return $this
     */
    public function setEmailVerificationActiveUntil(Carbon $emailVerificationActiveUntil): self
    {
        $this->emailVerificationActiveUntil = $emailVerificationActiveUntil;

        return $this;
    }

    public function getEmailVerificationActiveUntil(): Carbon
    {
        return $this->emailVerificationActiveUntil;
    }

    /**
     * @return $this
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
