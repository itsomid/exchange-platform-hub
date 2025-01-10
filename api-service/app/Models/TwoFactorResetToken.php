<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $email
 * @property string $token
 * @property Carbon $created_at
 */
class TwoFactorResetToken extends Model
{
    protected $fillable = [
        'email',
        'token',
    ];
}
