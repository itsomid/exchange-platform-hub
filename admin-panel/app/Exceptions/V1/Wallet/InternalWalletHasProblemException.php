<?php

namespace App\Exceptions\V1\Wallet;

use App\Exceptions\ServiceException;

class InternalWalletHasProblemException extends ServiceException
{
    protected $code = 500;
}