<?php

namespace App\Exceptions\V1\Wallet;

use App\Exceptions\ServiceException;

class InsufficientBalanceException extends ServiceException
{
    protected $code = 400;

    public function __construct($message = null, $code = null)
    {
        // If message contains currency placeholder, replace it
        if (str_contains($message, ':currency')) {
            // Extract currency from the message (assuming format "Insufficient BTC balance.")
            if (preg_match('/Insufficient\s+(\w+)\s+balance/', $message, $matches)) {
                $currency = $matches[1];
                $message = str_replace(':currency', $currency, trans('exceptions.' . static::class));
            }
        }

        parent::__construct($message, $code);
    }
}
