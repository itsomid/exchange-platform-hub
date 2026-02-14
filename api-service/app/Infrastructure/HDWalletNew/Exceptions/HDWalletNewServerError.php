<?php

namespace App\Infrastructure\HDWalletNew\Exceptions;

use Exception;

class HDWalletNewServerError extends Exception
{
    protected $message = 'HD Wallet New service encountered an error';
    protected $code = 500;
}
