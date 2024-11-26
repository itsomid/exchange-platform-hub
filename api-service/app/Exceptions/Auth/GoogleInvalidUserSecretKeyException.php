<?php

namespace App\Exceptions\Auth;

use App\Exceptions\ServiceException;

class GoogleInvalidUserSecretKeyException extends ServiceException
{
    protected $code = 400;
}
