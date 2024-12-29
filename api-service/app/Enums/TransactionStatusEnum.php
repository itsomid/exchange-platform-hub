<?php

namespace App\Enums;

enum TransactionStatusEnum: string
{
    case FAILED = 'failed';
    case PENDING = 'pending';
    case SUCCESS = 'success';
}
