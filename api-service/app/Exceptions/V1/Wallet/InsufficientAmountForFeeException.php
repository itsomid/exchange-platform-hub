<?php

namespace App\Exceptions\V1\Wallet;

use App\Exceptions\ServiceException;

class InsufficientAmountForFeeException extends ServiceException
{
    protected $code = 400;

    public function __construct($message = null, $code = null)
    {
        parent::__construct($message ?? trans('exceptions.' . static::class), $code);
    }
}
