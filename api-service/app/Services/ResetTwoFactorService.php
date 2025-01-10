<?php

namespace App\Services;

use App\Exceptions\Auth\ResetTwoFactor\TokenInvalidException;
use App\Mail\ResetTwoFactorMail;
use App\Models\TwoFactorResetToken;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ResetTwoFactorService
{
    public function __construct(private readonly UserRepositoryInterface $userRepository) {}

    public function requestDisable(string $email, string $userIp): void
    {
        $user = $this->userRepository->getUserByEmail($email);

        if (is_null($user) || empty($user->two_factor_secret)) {
            return;
        }

        $rawToken = Str::random(64);
        $encryptedToken = encrypt($rawToken.'|'.$userIp);

        TwoFactorResetToken::query()
            ->create([
                'email' => $email,
                'token' => hash('sha256', $rawToken),
            ]);

        //Send Email
        Mail::to($email)->send(new ResetTwoFactorMail($encryptedToken));
    }

    public function disable(string $email, string $encryptedToken, string $ip): void
    {
        $decryptedData = decrypt($encryptedToken);
        [$rawToken,$tokenIp] = explode('|', $decryptedData);

        $hashedToken = hash('sha256', $rawToken);
        $record = TwoFactorResetToken::query()->where('token', $hashedToken)->first();

        if ($tokenIp !== $ip || ! $record || now()->diffInMinutes($record->created_at) > 10 || $record->email !== $email) {
            throw new TokenInvalidException;
        }

        $user = $this->userRepository->getUserByEmail($email);
        $user->two_factor_secret = null;
        $user->save();

        $record->delete();
    }
}
