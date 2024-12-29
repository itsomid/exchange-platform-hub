<?php

namespace App\Exceptions\V1\OTC;

use App\Exceptions\ServiceException;

class InsufficientBalanceException extends ServiceException
{
    protected $code = 400;
}
