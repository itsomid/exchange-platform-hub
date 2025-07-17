<?php

namespace App\Services\Exchanges\Asset\Mexc\Enum;

enum MexcWithdrawStatusEnum: string
{
    case APPLY = 'apply';
    case AUDITING = 'auditing';
    case WAIT = 'wait';
    case PROCESSING = 'processing';
    case WAIT_PACKAGING = 'wait_packaging';
    case WAIT_CONFIRM = 'wait_confirm';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case CANCEL = 'cancel';
    case MANUAL = 'manual';
} 