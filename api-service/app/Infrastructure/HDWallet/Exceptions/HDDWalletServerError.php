<?php

namespace App\Infrastructure\HDWallet\Exceptions;

use App\Exceptions\ServiceException;

class HDDWalletServerError extends ServiceException
{
    protected $code = 500;
}
