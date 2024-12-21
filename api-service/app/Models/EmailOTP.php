<?php

namespace App\Models;

use App\Enums\EmailOTPActionEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string             $code
 * @property string             $email
 * @property EmailOTPActionEnum $action
 * @property Carbon             $created_at
 */
class EmailOTP extends Model
{
    protected $table = 'email_otp';

    protected $fillable = [
        'email', 'code', 'action',
    ];

    protected function casts(): array
    {
        return [
            'action' => EmailOTPActionEnum::class,
        ];
    }
}
