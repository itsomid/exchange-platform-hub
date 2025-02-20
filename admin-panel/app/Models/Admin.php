<?php

namespace App\Models;

use App\Filters\Filterable;
use App\Functions\Jalali;
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

    protected $casts = ['email_verified_at' => 'datetime', 'password' => 'hashed'];

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

    public function avatar()
    {
        $random = rand(1, 9);

        return is_null($this->avatar)
            ? asset("images/avatars/{$this->gender}/{$random}.png")
            : $this->avatar;
    }

    public function twoFAStatus()
    {
        return (bool)$this->two_factore_secret;
    }
    public function status()
    {
        return (bool)$this->is_active;
    }
}
