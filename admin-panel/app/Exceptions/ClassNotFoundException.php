<?php

namespace App\Exceptions;

class ClassNotFoundException extends ServiceException
{
    public function __construct($message = '', $code = 404)
    {
        parent::__construct($message, $code);
    }
}
