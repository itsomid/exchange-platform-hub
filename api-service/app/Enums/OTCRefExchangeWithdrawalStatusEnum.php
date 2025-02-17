<?php

namespace App\Enums;

enum OTCRefExchangeWithdrawalStatusEnum: string
{
    case COMPLETED = 'COMPLETED';
    case PENDING = 'PENDING';
    case CANCELLED = 'CANCELLED';
}
