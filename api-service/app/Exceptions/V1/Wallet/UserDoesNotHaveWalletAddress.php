<?php

namespace App\Exceptions\V1\Wallet;

use App\Exceptions\ServiceException;

class UserDoesNotHaveWalletAddress extends ServiceException
{
    protected $code = 400;
}
