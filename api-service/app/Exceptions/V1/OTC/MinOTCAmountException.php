<?php

namespace App\Exceptions\V1\OTC;

use App\Exceptions\ServiceException;

class MinOTCAmountException extends ServiceException
{
    protected $code = 422;
}