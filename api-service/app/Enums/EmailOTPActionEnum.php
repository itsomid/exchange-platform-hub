<?php

namespace App\Enums;

enum EmailOTPActionEnum: string
{
    case TWO_FACTOR_SETUP = 'two-factor-setup';
    case WITHDRAWAL = 'withdrawal';

    public function getLabel(): string
    {
        return match ($this) {
            self::TWO_FACTOR_SETUP => 'دو عاملی',
            self::WITHDRAWAL => 'برداشت',
        };
    }
}
