<?php

namespace App\Exceptions\System;

use App\Exceptions\ServiceException;

class OTPRateLimitException extends ServiceException
{
    protected $code = 429;

    public function __construct($message = null, $code = null)
    {
        parent::__construct($message ?? __('messages.otp.rate_limit'), $code ?? $this->code);
    }
}