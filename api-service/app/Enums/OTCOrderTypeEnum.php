<?php

namespace App\Enums;

enum OTCOrderTypeEnum: string
{
    case SUCCESS = 'success';
    case CANCELED = 'canceled';
    case PENDING = 'pending';
}
