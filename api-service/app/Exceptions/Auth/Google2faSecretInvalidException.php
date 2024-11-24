<?php

namespace App\Exceptions\Auth;

use App\Exceptions\ServiceException;

class Google2faSecretInvalidException extends ServiceException
{
    protected $code = 400;
}
