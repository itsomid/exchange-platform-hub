<?php

namespace App\Exceptions\Bot;

use App\Exceptions\ServiceException;

class BotTransferAmountTooLowException extends ServiceException
{
    protected $code = 422;

    public function __construct()
    {
        parent::__construct('حداقل مبلغ قابل انتقال ۲۰ تتر است.');
    }
}
