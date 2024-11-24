<?php

namespace App\Exceptions\Auth;

use App\Exceptions\ServiceException;

class InvalidVerificationTokenException extends ServiceException
{
    protected $code = 400;
}
