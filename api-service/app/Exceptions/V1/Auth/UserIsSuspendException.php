<?php

namespace App\Exceptions\V1\Auth;

use App\Exceptions\ServiceException;

class UserIsSuspendException extends ServiceException
{
    protected $code = 403;
}
