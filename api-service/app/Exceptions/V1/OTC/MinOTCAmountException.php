<?php

namespace App\Exceptions\V1\OTC;

use App\Exceptions\ServiceException;

class MinOTCAmountException extends ServiceException
{
    protected $code = 422;

    /**
     * @param string|null $message
     * @param int|null $code
     * @param float|null $min
     */
    public function __construct($message = null, $code = null, ?float $min = null)
    {
        parent::__construct($message, $code);
        $this->message = empty($message) ? trans('exceptions.'.static::class, ['min' => $min]) : $message;
        parent::__construct($this->message, $code);
    }
}