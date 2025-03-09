<?php

namespace App\Enums;

enum SpotOrderTypeEnum: string
{
    case MARKET = 'market';
    case LIMIT = 'limit';
}
