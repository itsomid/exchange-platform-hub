<?php

namespace App\Infrastructure\HDWalletNew\Exceptions;

use App\Exceptions\ServiceException;

class HDWalletNewServerError extends ServiceException
{
    protected $message = 'HD Wallet New service encountered an error';
    protected $code = 500;
}
