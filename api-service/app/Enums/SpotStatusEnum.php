<?php

namespace App\Enums;

enum SpotStatusEnum: string
{
    case NotEnoughBalance = 'Not_Enough_Balance';
    case BuyOrderSubmitted = 'Buy_Order_Submitted';
    case BuyOrderFailed = 'Buy_Order_Failed';
    case ConnectionLosses = 'Connection_Losses';

}
