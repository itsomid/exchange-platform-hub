<?php

namespace App\Exceptions\V1\Auth;

use App\Exceptions\ServiceException;

class UserNotVerifiedException extends ServiceException
{
    protected $code = 403;
    protected $message = 'حساب کاربری شما تایید نشده است';
}
