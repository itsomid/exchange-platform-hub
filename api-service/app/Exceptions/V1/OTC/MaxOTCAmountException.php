<?php

namespace App\Exceptions\V1\OTC;

use App\Exceptions\ServiceException;

class MaxOTCAmountException extends ServiceException
{
    protected $code = 422;
}