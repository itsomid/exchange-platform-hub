<?php

namespace App\Enums;

enum LockedBalanceTypeEnum: string
{
    case WITHDRAWAL = 'withdrawal';
    case OTC = 'otc';
    case SPOT = 'spot';
}
