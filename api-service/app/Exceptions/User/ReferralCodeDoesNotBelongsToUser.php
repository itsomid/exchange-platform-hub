<?php

namespace App\Exceptions\User;

use App\Exceptions\ServiceException;

class ReferralCodeDoesNotBelongsToUser extends ServiceException
{
    protected $code = 401;
}
