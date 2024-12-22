<?php

namespace App\Enums;

enum TransactionTypeEnum: string
{
    case BUY = 'buy';
    case SELL = 'sell';
    case DEPOSIT = 'deposit';
    case WITHDRAWAL= 'withdrawal';
    case REFERRAL = 'referral';
    case ADMIN_CREDIT = 'admin_credit';

    const array TYPE_LABEL = [
        self::BUY->value => 'خرید',
        self::SELL->value => 'فروش',
        self::DEPOSIT->value => 'واریز',
        self::WITHDRAWAL->value => 'برداشت',
        self::REFERRAL->value => 'دعوت از دوستان',
        self::ADMIN_CREDIT->value => 'اعتبار ادمین',
    ];

    const array TYPE_COLOR = [
        self::BUY->value => 'primary',
        self::SELL->value => 'primary',
        self::DEPOSIT->value => 'success',
        self::WITHDRAWAL->value => 'danger',
        self::REFERRAL->value => 'primary',
        self::ADMIN_CREDIT->value => 'info',
    ];
    const array TYPE_ICON = [
        self::BUY->value => 'money-bill-transfer',
        self::SELL->value => 'money-bill-transfer',
        self::DEPOSIT->value => 'arrow-down-to-bracket',
        self::WITHDRAWAL->value => 'arrow-up-from-bracket',
        self::REFERRAL->value => 'user-tag',
        self::ADMIN_CREDIT->value => 'user-tie-hair',
    ];

    public function label(): string
    {
        return self::TYPE_LABEL[$this->value] ?? '';
    }

    /**
     * Get color for the deposit status.
     *
     * @return string
     */
    public function color(): string
    {
        return self::TYPE_COLOR[$this->value] ?? '';
    }
    public function logo(): string
    {
        return self::TYPE_ICON[$this->value] ?? '';
    }
}
