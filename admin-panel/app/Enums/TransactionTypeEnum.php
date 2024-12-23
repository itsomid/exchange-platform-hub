<?php

namespace App\Enums;

enum TransactionTypeEnum: string
{
    case OTC_BUY = 'otc_buy';
    case OTC_SELL = 'otc_sell';
    case DEPOSIT = 'deposit';
    case WITHDRAWAL= 'withdrawal';
    case REFERRAL = 'referral';

    const array TYPE_LABEL = [
        self::OTC_BUY->value => 'خرید',
        self::OTC_SELL->value => 'فروش',
        self::DEPOSIT->value => 'واریز',
        self::WITHDRAWAL->value => 'برداشت',
        self::REFERRAL->value => 'دعوت از دوستان',
//        self::ADMIN_CREDIT->value => 'اعتبار ادمین',
    ];

    const array TYPE_COLOR = [
        self::OTC_BUY->value => 'primary',
        self::OTC_SELL->value => 'primary',
        self::DEPOSIT->value => 'success',
        self::WITHDRAWAL->value => 'danger',
        self::REFERRAL->value => 'primary',
//        self::ADMIN_CREDIT->value => 'info',
    ];
    const array TYPE_ICON = [
        self::OTC_BUY->value => 'money-bill-transfer',
        self::OTC_SELL->value => 'money-bill-transfer',
        self::DEPOSIT->value => 'arrow-down-left',
        self::WITHDRAWAL->value => 'arrow-up-right',
        self::REFERRAL->value => 'user-tag',
//        self::ADMIN_CREDIT->value => 'user-tie-hair',
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
