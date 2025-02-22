<?php

namespace App\Services\Exchanges\Enums;

enum CoinexWithdrawalError: int
{
    case Below_Minimum_Amount = 11022;
    case Address_White_List = 11050;

    public static function mapErrorToResponse(CoinexWithdrawalError $errorEnum): string
    {
        return match ($errorEnum) {
            CoinexWithdrawalError::Below_Minimum_Amount => 'مبلغ تجمیع تراکنش پایین تر از حد مجاز است.',
            CoinexWithdrawalError::Address_White_List => 'آدرس ولت در وایت لیست نمی باشد.',
            default => 'خطای ناشناخته، لطفا دوباره تلاش کنید.',
        };
    }
}
