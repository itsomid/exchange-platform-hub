<?php

namespace App\Models;

use App\Enums\UserStatusEnum;
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
use Laravel\Sanctum\PersonalAccessToken;

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
        'two_factor_secret',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'status' => UserStatusEnum::class,
    ];

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
        return $this->hasMany(ReferralCodeUsage::class, 'used_by');
    }
    public function ownerReferralCodeUsage(): HasMany
    {
        return $this->hasMany(ReferralCodeUsage::class, 'used_by')->where('type','introducer');
    }

    public function financialBlocks(): HasMany
    {
        return $this->hasMany(UserFinancialBlock::class, 'user_id')->orderBy('restricted_until', 'desc');
    }

    public function wallets()
    {
        return $this->hasMany(Wallet::class, 'user_id');
    }

    public function activeFinancialBlocks(): HasMany
    {
        return $this->hasMany(UserFinancialBlock::class, 'user_id')->where('restricted_until', '>', Carbon::now())->orderBy('restricted_until', 'desc');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function financialBlocksFrom(?string $action = null)
    {
        $query = $this->hasMany(UserFinancialBlock::class, 'user_id')
            ->where('restricted_until', '>', Carbon::now())
            ->orderBy('restricted_until', 'desc');

        if ($action) {
            $query->where('action', $action);
        }

        return $query;
    }

    public function personalAccessTokens(): HasMany
    {
        return $this->hasMany(PersonalAccessToken::class, 'tokenable_id');
    }

    public function latestActiveToken()
    {
        return $this->hasOne(PersonalAccessToken::class, 'tokenable_id')
            ->whereNotNull('last_used_at')
            ->latest('last_used_at'); // Orders by last_used_at DESC
    }

    public function savedAddresses()
    {
        return $this->hasMany(SavedAddress::class);
    }

    public function fullname()
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function twoFAStatus(): bool
    {
        return (bool) $this->two_factor_secret;
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

    public function setDetailOnToken($token)
    {
        $agent = new Agent;
        $device = $agent->platform().'-';
        $device = $device.$agent->browser();

        $token->accessToken->device = $device;
        $token->accessToken->ip = request()->ip();
        $token->accessToken->save();
    }

    /**
     * Send a password reset notification to the user.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $url = sprintf(config('frontend.reset-password-link'), $token);
        $this->notify(new ResetPasswordNotification($this, $url));
    }

    // ////////SCOPE/////////
    public function scopeOnline($query)
    {
        return $query->whereHas('latestActiveToken', function ($q) {
            $q->where('last_used_at', '>=', now()->subMinutes(10));
        });

    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInActive($query)
    {
        return $query->where('status', 'inactive');
    }

    // ////END SCOPE/////////
    public function getAvatarNameAttribute()
    {
        $firstName = $this->attributes['first_name'] ?? '';
        $lastName = $this->attributes['last_name'] ?? '';

        return strtoupper(substr($firstName, 0, 2)).' '.strtoupper(substr($lastName, 0, 2));
    }

    public function getAvatarUserNameAttribute()
    {
        $username = $this->attributes['username'] ?? '';

        return strtoupper(substr($username, 0, 2));
    }

    public function getActivityStatusAttribute()
    {
        if (! $this->latestActiveToken) {
            return 'offline';
        }

        if ($this->latestActiveToken->last_used_at > now()) {
            return 'offline'; // or 'unknown'
        }

        return (abs(now()->diffInMinutes($this->latestActiveToken->last_used_at)) < 10)
            ? 'online'
            : 'away';
    }

    public function getAvatarStatusAttribute()
    {
        return match ($this->activity_status) {
            'online' => 'success',
            'away' => 'warning',
            'offline' => 'secondary',
        };
    }

    public function generateAccessToken($minutes = 1): string
    {
        $user = $this;
        $tokenObject = $user->createToken(
            name: 'system',
            expiresAt: now()->addMinutes($minutes)
        );

        return $tokenObject->plainTextToken;
    }
}
