<?php

namespace App\Services\Exchanges\Enums;

enum CoinexWithdrawalError: int
{
    case Below_Minimum_Amount = 11022;
    case Address_White_List = 11050;
    case Asset_Insufficient = 11008;
    case Exceeding_Withdrawal_Decimal_Limit = 11029;

    public static function mapErrorToResponse(CoinexWithdrawalError $errorEnum): string
    {
        return match ($errorEnum) {
            CoinexWithdrawalError::Below_Minimum_Amount => 'مقدار تجمیع تراکنش پایین تر از حد مجاز است.',
            CoinexWithdrawalError::Address_White_List => 'آدرس ولت در وایت لیست نمی باشد.',
            CoinexWithdrawalError::Asset_Insufficient => 'عدم موجودی کافی (فی برداشت) برای انجام فرآیند تجمیع.',
            CoinexWithdrawalError::Exceeding_Withdrawal_Decimal_Limit => 'تعداد اعشار مقدار برداشت بیشتر از حد مجار است.',
            default => 'خطای ناشناخته، لطفا دوباره تلاش کنید.',
        };
    }
}
