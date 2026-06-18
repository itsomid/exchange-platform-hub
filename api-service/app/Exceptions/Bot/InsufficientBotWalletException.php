<?php

namespace App\Exceptions\Bot;

use App\Exceptions\ServiceException;

class InsufficientBotWalletException extends ServiceException
{
    protected $code = 422;

    public function __construct()
    {
        parent::__construct('موجودی کیف‌پول ربات کافی نیست.');
    }
}
