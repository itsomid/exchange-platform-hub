<?php

namespace App\Exceptions\V1\Wallet;

use App\Exceptions\ServiceException;

class InsufficientBalanceException extends ServiceException
{
    protected $code = 400;
}
