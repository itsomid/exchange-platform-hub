<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password
 * @property string $google2fa_secret
 * @property int    $id
 * @property string $two_factor_secret
 */
class User extends Authenticatable implements CanResetPassword, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'username',
        'introducer_code',
        'last_login',
        'last_ip_address',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login' => 'datetime',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function referredTransactions(): HasManyThrough
    {
        return $this->hasManyThrough(Transaction::class, ReferralCodeUsage::class, 'used_by', 'id', 'id', 'transaction_id');
    }

    public function getNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function emailVerification(): HasMany
    {
        return $this->hasMany(UserEmailVerification::class);
    }

    /**
     * Send a password reset notification to the user.
     *
     * @param string $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $url = sprintf(config('frontend.reset-password-link'), $token);
        $this->notify(new ResetPasswordNotification($this, $url));
    }

    /**
     * Generate the URL for the frontend application to verify the user's email.
     */
    public function getUrlForEmailVerification(): string
    {
        return sprintf(config('frontend.email-verification-link'), $this->getLatestToken());
    }

    /**
     * Retrieve the latest email verification token for the user.
     */
    public function getLatestToken(): int
    {
        return $this->emailVerification()->latest()->first()->token;
    }
}
