<?php

namespace App\Services\Exchanges\Asset\Coinex\Enum;

enum CoinexWithdrawalError: int
{
    case Below_Minimum_Amount = 11022;
    case Address_White_List = 11050;
    case Asset_Insufficient = 11008;
    case Asset_Not_Found = 11002;
    case Exceeding_Withdrawal_Decimal_Limit = 11029;
    case Amount_too_Small = 3127;
    case CET_Balance_Insufficient = 10001;


    public static function mapErrorToResponse(CoinexWithdrawalError $errorEnum): string
    {
        return match ($errorEnum) {
            CoinexWithdrawalError::Below_Minimum_Amount => 'مقدار تجمیع تراکنش پایین تر از حد مجاز است.',
            CoinexWithdrawalError::Address_White_List => 'آدرس ولت در وایت لیست نمی باشد.',
            CoinexWithdrawalError::Asset_Insufficient => 'عدم موجودی کافی (فی برداشت) برای انجام فرآیند تجمیع.',
            CoinexWithdrawalError::Asset_Not_Found => 'دارایی مورد نظر یا شبکه دارایی مورد نظر برای برداشت یافت نشد.',
            CoinexWithdrawalError::Exceeding_Withdrawal_Decimal_Limit => 'تعداد اعشار مقدار برداشت بیشتر از حد مجار است.',
            CoinexWithdrawalError::Amount_too_Small => 'مقدار معامله کوچکتر از حد مجاز است.',
            CoinexWithdrawalError::CET_Balance_Insufficient => 'موجودی CET برای پرداخت کارمزد کافی نیست.',
            default => 'خطای ناشناخته، لطفا دوباره تلاش کنید.',
        };
    }
}
