<?php

namespace App\Exceptions;

use Exception;

class InsufficientBalanceException extends Exception
{
    protected $code = 422;
    public function __construct()
    {
        parent::__construct('موجودی کیف‌پول کافی نیست.');
    }
}
