<?php

namespace App\Infrastructure\HDWallet\Exceptions;

use App\Exceptions\ServiceException;

class NotFoundException extends ServiceException
{
    protected $code = 404;
}
