<?php

namespace App\Enums;

enum FinancialBlockReasonsEnum :string
{
    case ADMIN = 'admin';
    case GROUP = 'group';
    case PASSWORD_CHANGED = 'password_changed';
    case DISABLE_TWO_FACTOR = 'disable_two_factor';

    const array TYPE_LABEL = [
        self::ADMIN->value => 'توسط ادمین',
        self::GROUP->value => 'به صورت گروهی',
        self::PASSWORD_CHANGED->value => 'تغییر رمز عبور',
        self::DISABLE_TWO_FACTOR->value => 'غیرفعال کردن دو مرحله ای',
    ];

    public function label()
    {
        return self::TYPE_LABEL[$this->value]??'';
    }

}
