<?php

namespace App\Exceptions\Bot;

use App\Exceptions\ServiceException;

class BotAutoTradeActiveException extends ServiceException
{
    protected $code = 422;

    public function __construct()
    {
        parent::__construct('برای برداشت از کیف پول ربات ابتدا باید خرید و فروش خودکار را خاموش کنید.');
    }
}
