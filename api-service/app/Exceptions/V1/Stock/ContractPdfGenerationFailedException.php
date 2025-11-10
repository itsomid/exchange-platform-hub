<?php

namespace App\Exceptions\V1\Stock;

use App\Exceptions\ServiceException;

class ContractPdfGenerationFailedException extends ServiceException
{
    protected $code = 500;
}
