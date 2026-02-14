<?php

namespace App\Exceptions\V1\OTC;

use App\Exceptions\ServiceException;

class MaxOTCAmountException extends ServiceException
{
    protected $code = 422;

    /**
     * @param string|null $message
     * @param int|null $code
     * @param float|null $max
     */
    public function __construct($message = null, $code = null, ?float $max = null)
    {
        parent::__construct($message, $code);
        $this->message = empty($message) ? trans('exceptions.'.static::class, ['max' => $max]) : $message;
        parent::__construct($this->message, $code);
    }
}