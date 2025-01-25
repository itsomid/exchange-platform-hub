<?php

namespace App\Enums;

enum OTCOrderExchangeTypeEnum: string
{
    case PENDING = 'pending';
    case FILLED = 'filled';
}
