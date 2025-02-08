<?php

namespace App\Exceptions\V1\OTC;

use App\Exceptions\ServiceException;

class TradeWasFiledException extends ServiceException
{
    protected $code = 400;
}
