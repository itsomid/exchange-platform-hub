<?php

namespace App\Models;

use App\Filters\Filterable;
use App\Notifications\ResetPasswordNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements CanResetPassword
{
    use Filterable, HasApiTokens, HasFactory, Notifiable;

    const NEW_TOKEN_INTERVAL = 20;

    const MAX_TOKENS = 2;

    public $filterNameSpace = 'App\Filters\UserFilter';

    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'username',
        'email',
        'mobile',
        'password',
        'status',
        'introducer_code',
        'kyc_status',
        'description',
        'support_description',
    ];

    protected $guarded = ['id'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    public function fullname()
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function referralCodes(): HasMany
    {
        return $this->hasMany(ReferralCode::class, 'user_id');
    }

    public function introducerReferral(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class, 'introducer_code', 'id');
    }

    public function referralCodeUsage(): HasMany
    {
        return $this->hasMany(ReferralCodeUsage::class,'used_by');
    }


    public function financialBlocks(): HasMany
    {
        return $this->hasMany(UserFinancialBlock::class,'user_id')->orderBy('restricted_until', 'desc');
    }

    public function activeFinancialBlocks() : HasMany
    {
        return $this->hasMany(UserFinancialBlock::class,'user_id')->where('restricted_until', '>', Carbon::now())->orderBy('restricted_until', 'desc');
    }

    public function financialBlocksFrom(string $action = null)
    {
        $query = $this->hasMany(UserFinancialBlock::class, 'user_id')
            ->where('restricted_until', '>', Carbon::now())
            ->orderBy('restricted_until', 'desc');

        if ($action) {
            $query->where('action', $action);
        }

        return $query;
    }

    public function wallets()
    {
        return $this->hasMany(Wallet::class,'user_id');
    }
    public function twoFAStatus()
    {
        return (bool)$this->two_factore_secret;
    }
    public function isLockedToSendToken(): bool
    {
        return $this->sms_lock_until && now()->lte($this->sms_lock_until) && ! app()->environment('local');
    }

    public function canGenerateToken(): bool
    {
        return empty($this->sms_token) || $this->sms_this_token_tries >= self::NEW_TOKEN_INTERVAL;
    }

    public function generateToken(): string
    {
        return
            config('app.env') === 'local'
                ? '11111'
                : str_pad(random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
    }

    public function setDetailOnToken($token)
    {
        $agent = new Agent;
        $device = $agent->platform().'-';
        $device = $device.$agent->browser();

        $token->accessToken->device = $device;
        $token->accessToken->ip = request()->ip();
        $token->accessToken->save();
    }

    public static function generateUsername($email)
    {
        // Extract the part of the email before the '@'
        $baseUsername = Str::before($email, '@');

        // Clean up the base username: remove special characters, limit length
        $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', $baseUsername);
        $baseUsername = Str::limit($baseUsername, 20, '');

        // Start with the base username
        $username = $baseUsername;

        // Check for uniqueness
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername.$counter; // Append a number if not unique
            $counter++;
        }

        return $username;
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


}
