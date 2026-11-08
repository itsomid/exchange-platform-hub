<?php

namespace App\Models;

use App\Filters\Filterable;
use App\Functions\Jalali;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @method static adminAndSupervisor()
 */
class Admin extends Authenticatable
{
    use Filterable, HasApiTokens, HasFactory, HasRoles, Jalali, Notifiable, TwoFactorAuthenticatable;

    public $filterNameSpace = 'App\Filters\AdminFilters';

    protected $fillable = ['mobile', 'email', 'first_name', 'last_name', 'password', 'gender', 'instagram', 'telegram', 'whatsapp'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['email_verified_at' => 'datetime', 'two_factor_confirmed_at' => 'datetime', 'password_changed_at' => 'datetime', 'password_change_deadline_at' => 'datetime', 'password' => 'hashed'];

    //-----------------------------------------------------------------------------------------------------//
    //---------------------------------------------    #Boot  ---------------------------------------------//

    public static function boot()
    {
        parent::boot();
        //        static::created(function (User $user) {
        //            RabbitMQ::onExchange('staff')->setRoutingKey('staff.staff')->withTopicType()->setData($user->toArray())->dispatch();
        //        });
    }

    //-----------------------------------------------------------------------------------------------------//
    //------------------------------------------    #Relations   ------------------------------------------//

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'user_id');
    }

    //-----------------------------------------------------------------------------------------------------//
    //------------------------------------------    #Scopes      ------------------------------------------//


    //-----------------------------------------------------------------------------------------------------//
    //------------------------------------------   #Attributes   ------------------------------------------//
    public function fullname()
    {
        return $this->first_name.' '.$this->last_name;
    }

    /**
     * Every selectable avatar as a key relative to public/images/avatars, e.g. "female/3.png".
     *
     * @return string[]
     */
    public static function availableAvatars(): array
    {
        static $avatars = null;

        if ($avatars === null) {
            $avatars = [];
            foreach (['female', 'male'] as $gender) {
                $files = glob(public_path("images/avatars/{$gender}/*.png")) ?: [];
                natsort($files);
                foreach ($files as $file) {
                    $avatars[] = $gender.'/'.basename($file);
                }
            }
        }

        return $avatars;
    }

    /** The chosen avatar key; admins who never chose one get a stable default derived from their id and gender. */
    public function avatarKey(): string
    {
        $available = self::availableAvatars();

        if ($this->avatar && in_array($this->avatar, $available, true)) {
            return $this->avatar;
        }

        $gender = $this->gender === 'male' ? 'male' : 'female';
        $pool = array_values(array_filter($available, fn ($key) => str_starts_with($key, $gender.'/')));

        return $pool[$this->id % count($pool)];
    }

    public const COIN_AVATAR_PREFIX = 'coin:';

    /** Generated / uploaded avatars are stored on the public disk as avatars/custom/{adminId}-{random}.png */
    public static function isCustomAvatarPath(?string $value): bool
    {
        return (bool) $value && preg_match('#^avatars/custom/\d+-[a-z0-9]+\.png$#', $value) === 1;
    }

    /** URL of a coin-logo or custom (generated / uploaded) avatar, or null when none is set or its file is gone. */
    private function customAvatarUrl(): ?string
    {
        $value = $this->avatar;

        if (self::isCustomAvatarPath($value) && is_file(storage_path("app/public/{$value}"))) {
            return asset("storage/{$value}");
        }

        if ($value && str_starts_with($value, self::COIN_AVATAR_PREFIX)) {
            $logo = substr($value, strlen(self::COIN_AVATAR_PREFIX));

            return Currency::logoExists($logo) ? Currency::logoUrl(basename($logo)) : null;
        }

        return null;
    }

    /** The stored value the avatar picker should mark as current. */
    public function avatarSelection(): string
    {
        return $this->customAvatarUrl() !== null ? $this->avatar : $this->avatarKey();
    }

    public function avatar()
    {
        if ($this->avatar && str_starts_with($this->avatar, 'http')) {
            return $this->avatar;
        }

        return $this->customAvatarUrl() ?? asset('images/avatars/'.$this->avatarKey());
    }

    /** 2FA is active only after the user confirmed a valid code. */
    public function twoFAStatus(): bool
    {
        return $this->hasEnabledTwoFactorAuthentication();
    }

    /** In production the panel is locked (profile only) until 2FA is active. */
    public function mustSetUpTwoFactor(): bool
    {
        return app()->isProduction() && ! $this->twoFAStatus();
    }


    public const PASSWORD_GRACE_DAYS = 6;
    public const PASSWORD_MAX_AGE_MONTHS = 3;

    /** True when the admin never changed the password or it is older than the allowed age. */
    public function passwordChangeIsDue(): bool
    {
        return is_null($this->password_changed_at)
            || $this->password_changed_at->copy()->addMonths(self::PASSWORD_MAX_AGE_MONTHS)->isPast();
    }

    /**
     * End of the grace period, or null when no change is due.
     * The countdown starts the first time a due password is noticed and is stored.
     */
    public function passwordChangeDeadline(): ?Carbon
    {
        if (! $this->passwordChangeIsDue()) {
            return null;
        }

        if (is_null($this->password_change_deadline_at)) {
            $deadline = now()->addDays(self::PASSWORD_GRACE_DAYS);

            static::whereKey($this->getKey())->update(['password_change_deadline_at' => $deadline]);
            $this->setAttribute('password_change_deadline_at', $deadline)->syncOriginalAttribute('password_change_deadline_at');
        }

        return $this->password_change_deadline_at;
    }

    /** Grace period is over: only the profile pages stay reachable. */
    public function passwordChangeOverdue(): bool
    {
        return (bool) $this->passwordChangeDeadline()?->isPast();
    }

    /** Stores the new password and restarts the 6-month cycle. */
    public function changePassword(string $plainPassword): void
    {
        $this->forceFill([
            'password' => $plainPassword, // hashed by the model cast
            'password_changed_at' => now(),
            'password_change_deadline_at' => null,
        ])->save();
    }

    /** Secret generated but the first code was not confirmed yet. */
    public function twoFAPending(): bool
    {
        return ! is_null($this->two_factor_secret) && is_null($this->two_factor_confirmed_at);
    }
    public function status()
    {
        return (bool)$this->is_active;
    }
}
