<?php

namespace App\Exceptions\V1\OTC;

use App\Exceptions\ServiceException;

class BuyTradeWasFiledException extends ServiceException
{
    protected $code = 400;

    /**
     * @param int $code
     */
    public function __construct($message = null, $code = null, ?string $marketName = null)
    {
        parent::__construct($message, $code);
        $this->message = empty($message) ? trans('exceptions.'.static::class, ['marketName' => $marketName]) : $message;
        parent::__construct($this->message, $code);
    }
}
