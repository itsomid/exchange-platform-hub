<?php

namespace App\Enums;

enum OTCOrderStatusEnum: string
{
    case SUCCESS = 'success';
    case CANCELED = 'canceled';
    case PENDING = 'pending';
}
