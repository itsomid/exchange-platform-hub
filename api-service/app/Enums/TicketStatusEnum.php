<?php

namespace App\Enums;

use App\Models\Deposit;

enum TicketStatusEnum: string
{
    case OPEN = 'open'; // باز
    case CLOSED = 'closed';  // بسته شده
    case InProgress = 'in_progress'; // در حال بررسی
    case WaitingForCustomer = 'waiting_for_customer'; // در انتظار پاسخ مشتری
    case WaitingForSupport = 'waiting_for_support'; // در انتظار پاسخ پشتیبانی
    case RESOLVED = 'resolved'; // حل شده
    case REOPENED = 'reopened'; // مجدداً باز شده

    // get type class
    const array TYPE_LABEL = [
        self::OPEN->value => 'باز',
        self::InProgress->value => 'در حال بررسی',
        self::WaitingForCustomer->value => 'در انتظار پاسخ مشتری',
        self::WaitingForSupport->value => 'در انتظار پاسخ پشتیبانی',
        self::RESOLVED->value => 'حل شده',
        self::CLOSED->value => 'بسته شده',
        self::REOPENED->value => 'مجدداً باز شده',
    ];

    const array TYPE_COLOR = [
        self::OPEN->value => 'primary',
        self::InProgress->value => 'warning',
        self::WaitingForCustomer->value => 'success',
        self::WaitingForSupport->value => 'warning',
        self::RESOLVED->value => 'success',
        self::CLOSED->value => 'secondary',
        self::REOPENED->value => 'warning',
    ];

    public function label(): string
    {
        return self::TYPE_LABEL[$this->value] ?? '';
    }

    /**
     * Get color for the deposit status.
     */
    public function color(): string
    {
        return self::TYPE_COLOR[$this->value] ?? '';
    }
}
