<?php

namespace App\Enums;

enum LockedBalanceTypeEnum: string
{
    case WITHDRAWAL = 'withdrawal';
    case ADMIN = 'admin';
    case SPOT = 'spot';
}
