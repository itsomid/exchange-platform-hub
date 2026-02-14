<?php

namespace App\Infrastructure\HDWalletNew\Exceptions;

use Exception;

class HDWalletNewUnavailable extends Exception
{
    protected $message = 'HD Wallet New service is currently unavailable';
    protected $code = 503;
}
