<?php

namespace App\Exceptions\V1\Wallet;

use App\Exceptions\ServiceException;

class UserDoesNotHaveWalletChainAddress extends ServiceException
{
    protected $code = 400;
}
