<?php

namespace App\Services\Exchanges\Asset\Enum;

enum WithdrawMethodEnum: string
{
    case ON_CHAIN = 'on_chain';
    case INTER_USER = 'inter_user';
}
