<?php

namespace App\Enums;

enum SpotStatusEnum: string
{
    case NotEnoughBalance = 'Not_Enough_Balance';
    case BuyOrderSubmitted = 'Buy_Order_Submitted';
    case AmountTooSmall = 'Amount_Too_Small';
    case BuyOrderFailed = 'Buy_Order_Failed';
    case ConnectionLosses = 'Connection_Losses';
    case PriceDifferenceTooLarge = 'PRICE_DIFFERENCE_TOO_LARGE';
}
