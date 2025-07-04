<?php

namespace App\Exceptions\Auth;

use App\Exceptions\ServiceException;

class IncompleteProfileException extends ServiceException
{
    protected $code = 403;
} 