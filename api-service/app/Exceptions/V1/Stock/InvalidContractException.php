<?php

namespace App\Exceptions\V1\Stock;

use App\Exceptions\ServiceException;

class InvalidContractException extends ServiceException
{
    protected $code = 400;
} 