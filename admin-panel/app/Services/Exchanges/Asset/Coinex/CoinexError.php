<?php

namespace App\Services\Exchanges\Asset\Coinex;

enum CoinexError: int
{
    case SERVICE_BUSY = 3008;
    case INSUFFICIENT_BALANCE = 3109;
    case BELOW_MIN_ORDER = 3127;
    case PRICE_DIFFERENCE_TOO_LARGE = 3606;
    case ORDER_CANCELLATION_PROHIBITED = 3610;
    case ASK_PRICE_TOO_LOW = 3612;
    case BID_PRICE_TOO_HIGH = 3613;
    case EST_FILLED_PRICE_DEVIATION = 3614;
    case ORDER_PRICE_INDEX_DEVIATION = 3615;
    case ORDER_PRICE_EXCEEDS_BID = 3616;
    case ORDER_PRICE_EXCEEDS_ASK = 3617;
    case ORDER_PRICE_INDEX_DEVIATION_HIGH = 3618;
    case ORDER_TRIGGER_PRICE_DEVIATION_HIGH = 3619;
    case MARKET_ORDER_UNAVAILABLE = 3620;
    case ORDER_CANNOT_BE_EXECUTED = 3621;
    case MAKER_ONLY_CANCELED = 3622;
    case LOW_MARKET_DEPTH_1 = 3627;
    case LOW_MARKET_DEPTH_2 = 3628;
    case LOW_MARKET_DEPTH_3 = 3629;
    case ORDER_PRICE_EXCEEDS_BID_2 = 3632;
    case ORDER_PRICE_EXCEEDS_ASK_2 = 3633;
    case FILLED_PRICE_INDEX_DEVIATION_HIGH_1 = 3634;
    case FILLED_PRICE_INDEX_DEVIATION_HIGH_2 = 3635;
    case PROTECTION_PERIOD = 3638;
    case INCORRECT_REQUEST_PARAMS = 3639;
    case SERVICE_UNAVAILABLE = 4001;
    case REQUEST_TIMEOUT = 4002;
    case INTERNAL_ERROR = 4003;
    case PARAMETER_ERROR = 4004;
    case ABNORMAL_ACCESS_ID = 4005;
    case SIGNATURE_VERIFICATION_FAILED = 4006;
    case IP_PROHIBITED = 4007;
    case ABNORMAL_SIGNATURE_VALUE = 4008;
    case ABNORMAL_REQUEST_METHOD = 4009;
    case EXPIRED_REQUEST = 4010;
    case USER_PROHIBITED_ACCESS = 4011;
    case SIGNATURE_EXPIRED = 4017;
    case ENDPOINT_DEPRECATED = 4018;
    case USER_PROHIBITED_TRADING = 4115;
    case MARKET_TRADING_PROHIBITED = 4117;
    case RATE_LIMIT_TRIGGERED = 4123;
    case FUTURES_TRADING_PROHIBITED = 4130;
    case TRADING_PROHIBITED = 4158;
    case REQUEST_TOO_FREQUENT = 4213;
    case INSUFFICIENT_SUBACCOUNT_PERMISSIONS = 4512;

    public static function mapErrorToResponse(?CoinexError $errorEnum): string
    {
        if ($errorEnum === null) {
            return 'خطای ناشناخته، لطفا دوباره تلاش کنید.';
        }
        
        return match ($errorEnum) {
            CoinexError::SERVICE_BUSY => 'سرویس مشغول است، لطفا بعدا تلاش کنید.',
            CoinexError::INSUFFICIENT_BALANCE => 'موجودی کافی نیست، مقدار سفارش را تنظیم کنید یا واریز دیگری انجام دهید.',
            CoinexError::BELOW_MIN_ORDER => 'مقدار سفارش کمتر از حداقل مقدار مورد نیاز است. لطفا مقدار سفارش را تنظیم کنید.',
            CoinexError::PRICE_DIFFERENCE_TOO_LARGE => 'تفاوت قیمت بین قیمت سفارش و آخرین قیمت بیش از حد است. لطفا مقدار سفارش را تنظیم کنید.',
            CoinexError::ORDER_CANCELLATION_PROHIBITED => 'لغو سفارش در دوره Call Auction ممنوع است.',
            CoinexError::ASK_PRICE_TOO_LOW => 'قیمت پیشنهادی فروش کمتر از پایین‌ترین قیمت پیشنهادی فعلی است. لطفا مقدار را کاهش دهید.',
            CoinexError::BID_PRICE_TOO_HIGH => 'قیمت پیشنهادی خرید بیشتر از بالاترین قیمت پیشنهادی فعلی است. لطفا مقدار را کاهش دهید.',
            CoinexError::INTERNAL_ERROR => 'خطای داخلی، لطفا با پشتیبانی تماس بگیرید.',
            CoinexError::PARAMETER_ERROR => 'خطای پارامتر، لطفا پارامترهای درخواست را بررسی کنید.',
            CoinexError::REQUEST_TIMEOUT => 'زمان درخواست به پایان رسید، لطفا بعدا تلاش کنید.',
            CoinexError::RATE_LIMIT_TRIGGERED => 'محدودیت نرخ درخواست اعمال شده است، لطفا نرخ درخواست خود را کاهش دهید.',
            default => 'خطای ناشناخته، لطفا دوباره تلاش کنید.',
        };
    }
}
