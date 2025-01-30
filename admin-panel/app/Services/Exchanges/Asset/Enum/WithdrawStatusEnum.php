<?php

namespace App\Services\Exchanges\Asset\Enum;

enum WithdrawStatusEnum: string
{
    case CREATED = 'created';
    case AUDIT_REQUIRED = 'audit_required';
    case AUDITED = 'audited';
    case PROCESSING = 'processing';
    case CONFIRMING = 'confirming';
    case FINISHED = 'finished';
    case cancelled = 'cancelled';
    case CANCELLATION_FAILED = 'cancellation_failed';
    case FAILED = 'failed';
}
