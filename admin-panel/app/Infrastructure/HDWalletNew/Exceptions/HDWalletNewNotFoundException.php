<?php

namespace App\Infrastructure\HDWalletNew\Exceptions;

use Exception;

class HDWalletNewNotFoundException extends Exception
{
    protected $message = 'Resource not found in HD Wallet New service';
    protected $code = 404;
}
