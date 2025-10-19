<?php

namespace App\Infrastructure\HDWallet\Exceptions;

use App\Exceptions\ServiceException;

class HDDWalletUnavailable extends ServiceException
{
    protected $code = 503;
}