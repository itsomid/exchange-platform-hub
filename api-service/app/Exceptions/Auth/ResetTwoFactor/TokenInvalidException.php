<?php

namespace App\Exceptions\Auth\ResetTwoFactor;

use App\Exceptions\ServiceException;

class TokenInvalidException extends ServiceException
{
    protected $code = 400;
}
