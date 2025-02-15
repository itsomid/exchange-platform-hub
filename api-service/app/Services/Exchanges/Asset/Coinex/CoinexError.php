<?php

namespace App\Services\Exchanges\Asset\Coinex;

enum CoinexError: int
{
    case SERVICE_BUSY = 3008; // سرویس مشغول است، لطفا بعدا تلاش کنید.
    case INSUFFICIENT_BALANCE = 3109; // موجودی کافی نیست، مقدار سفارش را تنظیم کنید یا واریز دیگری انجام دهید.
    case BELOW_MIN_ORDER = 3127; // مقدار سفارش کمتر از حداقل مقدار مورد نیاز است. لطفا مقدار سفارش را تنظیم کنید.
    case PRICE_DIFFERENCE_TOO_LARGE = 3606; // تفاوت قیمت بین قیمت سفارش و آخرین قیمت بیش از حد است. لطفا مقدار سفارش را تنظیم کنید.
    case ORDER_CANCELLATION_PROHIBITED = 3610; // لغو سفارش در دوره Call Auction ممنوع است.
    case ASK_PRICE_TOO_LOW = 3612; // قیمت پیشنهادی فروش کمتر از پایین‌ترین قیمت پیشنهادی فعلی است. لطفا مقدار را کاهش دهید.
    case BID_PRICE_TOO_HIGH = 3613; // قیمت پیشنهادی خرید بیشتر از بالاترین قیمت پیشنهادی فعلی است. لطفا مقدار را کاهش دهید.
    case EST_FILLED_PRICE_DEVIATION = 3614; // انحراف بین قیمت تخمینی پر شدن سفارش و قیمت شاخص زیاد است. لطفا مقدار را کاهش دهید.
    case ORDER_PRICE_INDEX_DEVIATION = 3615; // انحراف بین قیمت سفارش شما و قیمت شاخص زیاد است. لطفا قیمت سفارش را تنظیم کنید و دوباره تلاش کنید.
    case ORDER_PRICE_EXCEEDS_BID = 3616; // قیمت سفارش از بالاترین قیمت پیشنهادی فعلی بیشتر است. لطفا قیمت سفارش را تنظیم کنید.
    case ORDER_PRICE_EXCEEDS_ASK = 3617; // قیمت سفارش از پایین‌ترین قیمت پیشنهادی فعلی بیشتر است. لطفا قیمت سفارش را تنظیم کنید.
    case ORDER_PRICE_INDEX_DEVIATION_HIGH = 3618; // انحراف بین قیمت سفارش شما و قیمت شاخص بسیار زیاد است. لطفا قیمت سفارش را تنظیم کنید.
    case ORDER_TRIGGER_PRICE_DEVIATION_HIGH = 3619; // انحراف بین قیمت سفارش شما و قیمت ماشه زیاد است. لطفا قیمت سفارش را تنظیم کنید.
    case MARKET_ORDER_UNAVAILABLE = 3620; // ارسال سفارش بازار به دلیل عمق ناکافی بازار در حال حاضر امکان‌پذیر نیست.
    case ORDER_CANNOT_BE_EXECUTED = 3621; // این سفارش نمی‌تواند به طور کامل اجرا شود و لغو شده است.
    case MAKER_ONLY_CANCELED = 3622; // این سفارش نمی‌تواند فقط به عنوان Maker ثبت شود و لغو شده است.
    case LOW_MARKET_DEPTH_1 = 3627; // عمق بازار کم است، لطفا مقدار سفارش را کاهش دهید و دوباره تلاش کنید.
    case LOW_MARKET_DEPTH_2 = 3628; // عمق بازار کم است، لطفا مقدار سفارش را کاهش دهید و دوباره تلاش کنید.
    case LOW_MARKET_DEPTH_3 = 3629; // عمق بازار کم است، لطفا مقدار سفارش را کاهش دهید و دوباره تلاش کنید.
    case ORDER_PRICE_EXCEEDS_BID_2 = 3632; // قیمت سفارش از بالاترین قیمت پیشنهادی فعلی بیشتر است. لطفا قیمت سفارش را تنظیم کنید.
    case ORDER_PRICE_EXCEEDS_ASK_2 = 3633; // قیمت سفارش از پایین‌ترین قیمت پیشنهادی فعلی بیشتر است. لطفا قیمت سفارش را تنظیم کنید.
    case FILLED_PRICE_INDEX_DEVIATION_HIGH_1 = 3634; // انحراف قیمت پر شدن سفارش و قیمت شاخص زیاد است. لطفا مقدار را کاهش دهید.
    case FILLED_PRICE_INDEX_DEVIATION_HIGH_2 = 3635; // انحراف قیمت پر شدن سفارش و قیمت شاخص زیاد است. لطفا مقدار را کاهش دهید.
    case PROTECTION_PERIOD = 3638; // در دوره حفاظت هستید، فقط سفارشات Maker Only و لغو سفارشات مجاز است.
    case INCORRECT_REQUEST_PARAMS = 3639; // پارامترهای درخواست اشتباه هستند. لطفا بررسی کنید.
    case SERVICE_UNAVAILABLE = 4001; // سرویس در دسترس نیست، لطفا بعدا تلاش کنید.
    case REQUEST_TIMEOUT = 4002; // زمان درخواست به پایان رسید، لطفا بعدا تلاش کنید.
    case INTERNAL_ERROR = 4003; // خطای داخلی، لطفا با پشتیبانی تماس بگیرید.
    case PARAMETER_ERROR = 4004; // خطای پارامتر، لطفا پارامترهای درخواست را بررسی کنید.
    case ABNORMAL_ACCESS_ID = 4005; // Access ID غیرعادی است، لطفا مقدار X-COINEX-KEY را بررسی کنید.
    case SIGNATURE_VERIFICATION_FAILED = 4006; // تأیید امضا ناموفق بود، لطفا امضا را بررسی کنید.
    case IP_PROHIBITED = 4007; // آدرس IP ممنوع شده است، لطفا لیست سفید یا آدرس IP را بررسی کنید.
    case ABNORMAL_SIGNATURE_VALUE = 4008; // مقدار امضای X-COIN-SIGN غیرعادی است، لطفا بررسی کنید.
    case ABNORMAL_REQUEST_METHOD = 4009; // روش درخواست غیرعادی است، لطفا بررسی کنید.
    case EXPIRED_REQUEST = 4010; // درخواست منقضی شده است، لطفا بعدا تلاش کنید.
    case USER_PROHIBITED_ACCESS = 4011; // کاربر مجاز به دسترسی نیست، لطفا با پشتیبانی تماس بگیرید.
    case SIGNATURE_EXPIRED = 4017; // امضا منقضی شده است، لطفا دوباره تلاش کنید.
    case ENDPOINT_DEPRECATED = 4018; // این نقطه پایانی منسوخ شده است، لطفا از نسخه جدید استفاده کنید.
    case USER_PROHIBITED_TRADING = 4115; // کاربر مجاز به معامله نیست، لطفا با پشتیبانی تماس بگیرید.
    case MARKET_TRADING_PROHIBITED = 4117; // معامله در این بازار ممنوع است، لطفا بعدا تلاش کنید.
    case RATE_LIMIT_TRIGGERED = 4123; // محدودیت نرخ درخواست اعمال شده است، لطفا نرخ درخواست خود را کاهش دهید.
    case FUTURES_TRADING_PROHIBITED = 4130; // معاملات آتی ممنوع است، لطفا بعدا تلاش کنید.
    case TRADING_PROHIBITED = 4158; // معامله ممنوع است، لطفا بعدا تلاش کنید.
    case REQUEST_TOO_FREQUENT = 4213; // درخواست‌ها خیلی زیاد است، لطفا بعدا تلاش کنید.
    case INSUFFICIENT_SUBACCOUNT_PERMISSIONS = 4512; // مجوزهای زیر حساب ناکافی است، لطفا بررسی کنید.

    public static function mapErrorToResponse(CoinexError $errorEnum): string
    {
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
