<?php

namespace App\Exceptions\Bot;

use App\Exceptions\ServiceException;

class BotTermsNotAcceptedException extends ServiceException
{
    protected $code = 403;

    public function __construct()
    {
        parent::__construct('ابتدا باید شرایط و قوانین سیستم معاملات خودکار را بپذیرید.');
    }
}
