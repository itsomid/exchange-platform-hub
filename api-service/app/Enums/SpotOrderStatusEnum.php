<?php

namespace App\Enums;

enum SpotOrderStatusEnum: string
{
    case OPEN = 'open';
    case COMPLETED = 'completed';
    case CANCELED = 'canceled';
}
