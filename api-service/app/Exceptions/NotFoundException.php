<?php

namespace App\Exceptions;

class NotFoundException extends ServiceException
{
    protected $code = 404;
}
