<?php

namespace App\Exceptions;

use Exception;

class ServiceException extends Exception
{
    protected $message;

    protected $code;

    public function __construct($message = null, $code = null)
    {
        if (! is_null($code)) {
            $this->code = $code;
        }
        $this->message = empty($message) ? trans('exceptions.'.static::class) : $message;
        parent::__construct($this->message, $code);
    }

    /**
     * Assign Status Code
     *
     * @return $this
     */
    public function setStatusCode(int $code): self
    {
        $this->code = $code;

        return $this;
    }
}
