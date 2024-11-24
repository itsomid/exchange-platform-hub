<?php

namespace App\Exceptions;

use Exception;

class ServiceException extends Exception
{
    protected $message;

    protected $code;

    public function __construct($message = '', $code = 500)
    {
        $this->code = $code;
        $this->message = empty($message) ? trans('exceptions.'.static::class) : $message;
        parent::__construct($this->message, $code);
    }
}
