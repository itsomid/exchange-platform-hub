<?php

namespace App\Exceptions;

use Exception;

class ReferralCodeSystemDisabledException extends ServiceException
{
    protected $message = 'The referral code system is currently disabled.';
    protected $code = 403;
}
