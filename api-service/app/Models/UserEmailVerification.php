<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $token
 * @property int    $user_id
 * @property Carbon $expiration_date
 */
class UserEmailVerification extends Model
{
    protected $fillable = [
        'user_id', 'token', 'expiration_date',
    ];

    protected function casts(): array
    {
        return [
            'expiration_date' => 'datetime',
        ];
    }
}
