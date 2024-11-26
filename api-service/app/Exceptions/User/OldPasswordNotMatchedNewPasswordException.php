<?php

namespace App\Exceptions\User;

use App\Exceptions\ServiceException;

class OldPasswordNotMatchedNewPasswordException extends ServiceException
{
    protected $code = 400;
}
