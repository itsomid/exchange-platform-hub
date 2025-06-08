<?php

namespace App\Exceptions\Auth;

use App\Exceptions\ServiceException;

class InvalidUsernameOrPasswordException extends ServiceException
{
    protected $code = 400;
}
