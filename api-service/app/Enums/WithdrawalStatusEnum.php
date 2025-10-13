<?php

namespace App\Enums;

enum WithdrawalStatusEnum: string
{
    case PENDING = 'pending';
    case AWAITING_APPROVAL = 'awaiting_approval';
    case QUEUED = 'queued';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REJECTED = 'rejected';
}
