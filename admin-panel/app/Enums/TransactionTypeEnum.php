<?php

namespace App\Enums;

enum TransactionTypeEnum: string
{
    case BUY = 'buy';
    case SELL = 'sell';
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';
    case REFERRAL = 'referral';
    case FEE = 'fee';
    case ًREF_EXCHANGE = 'ref_exchange';

    const array TYPE_LABEL = [
        self::BUY->value => 'دریافت',//دریافت
        self::SELL->value => 'پرداخت',//پرداخت
        self::DEPOSIT->value => 'واریز',
        self::WITHDRAWAL->value => 'برداشت',
        self::REFERRAL->value => 'دعوت از دوستان',
        self::FEE->value => 'کارمزد',
        self::ًREF_EXCHANGE->value => 'صرافی مرجع',
        //        self::ADMIN_CREDIT->value => 'اعتبار ادمین',
    ];

    const array TYPE_COLOR = [
        self::BUY->value => 'success',
        self::SELL->value => 'danger',
        self::DEPOSIT->value => 'success',
        self::WITHDRAWAL->value => 'danger',
        self::REFERRAL->value => 'primary',
        self::FEE->value => 'info',
        self::ًREF_EXCHANGE->value => 'info',
        //        self::ADMIN_CREDIT->value => 'info',
    ];

    const array TYPE_ICON = [
        self::BUY->value => 'money-bill-transfer',
        self::SELL->value => 'money-bill-transfer',
        self::DEPOSIT->value => 'arrow-down-left',
        self::WITHDRAWAL->value => 'arrow-up-right',
        self::REFERRAL->value => 'user-tag',
        self::FEE->value => 'hand-holding-dollar',
        self::ًREF_EXCHANGE->value => 'hand-holding-dollar',

        //        self::ADMIN_CREDIT->value => 'user-tie-hair',
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

    public function icon(): string
    {
        return self::TYPE_ICON[$this->value] ?? '';
    }
}
