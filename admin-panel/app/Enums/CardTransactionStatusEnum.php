<?php

namespace App\Enums;

enum CardTransactionStatusEnum: string
{
    case Pending = 'pending';
    case Approved = 'complete';
}
