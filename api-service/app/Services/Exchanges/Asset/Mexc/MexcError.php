<?php

namespace App\Services\Exchanges\Asset\Mexc;

enum MexcError: int
{
    // General Errors
    case UNKNOWN_ERROR = -1000; // خطای ناشناخته
    case DISCONNECTED = -1001; // قطع ارتباط
    case UNAUTHORIZED = 401; // احراز هویت ناموفق
    case ACCESS_DENIED = 403; // دسترسی غیرمجاز
    case TOO_MANY_REQUESTS = 429; // تعداد درخواست بیش از حد مجاز
    case SERVICE_UNAVAILABLE = 500; // سرویس در دسترس نیست
    
    // Authentication Errors
    case APIKEY_INVALID = 700001; // کلید API نامعتبر است
    case SIGNATURE_INVALID = 700002; // امضای نامعتبر
    case TIMESTAMP_INVALID = 700003; // زمان نامعتبر
    case IP_RESTRICTED = 700004; // آدرس IP مجاز نیست
    case CONTENT_TYPE_INVALID = 700013; // نوع محتوا نامعتبر است
    
    // Parameter Errors
    case PARAM_ERROR = 33333; // خطای پارامتر
    case PARAM_EMPTY = 400; // پارامتر خالی است
    case PARAM_MISSING = 40001; // پارامتر مورد نیاز وجود ندارد
    case PARAM_INVALID = 40002; // پارامتر نامعتبر است
    
    // Order Errors
    case ORDER_MIN_AMOUNT = 30002; // حداقل مقدار سفارش رعایت نشده است
    case ORDER_MAX_AMOUNT = 30003; // حداکثر مقدار سفارش بیش از حد مجاز است
    case ORDER_PRICE_TOO_HIGH = 30004; // قیمت سفارش بیش از حد مجاز است
    case ORDER_PRICE_TOO_LOW = 30005; // قیمت سفارش کمتر از حد مجاز است
    case INSUFFICIENT_BALANCE = 10101; // موجودی کافی نیست
    case SYMBOL_INVALID = 30014; // نماد نامعتبر است
    case ORDER_TYPE_INVALID = 30041; // نوع سفارش نامعتبر است
    case ORDER_SIDE_INVALID = 30042; // جهت سفارش نامعتبر است
    case ORDER_NOT_FOUND = 30043; // سفارش یافت نشد
    case ORDER_LOCKED = 30044; // سفارش قفل شده است
    case ORDER_PARTIALLY_FILLED = 30045; // سفارش به صورت جزئی پر شده است
    case ORDER_FILLED = 30046; // سفارش پر شده است
    case ORDER_CANCELED = 30047; // سفارش لغو شده است
    case ORDER_CANCELING = 30048; // سفارش در حال لغو است
    case DUPLICATE_ORDER = 30049; // سفارش تکراری است
    case UNKNOWN_ORDER = -2011; // سفارش ناشناخته
    
    // Withdrawal Errors
    case WITHDRAWAL_SUSPENDED = 50001; // برداشت معلق شده است
    case WITHDRAWAL_AMOUNT_TOO_SMALL = 50002; // مقدار برداشت خیلی کم است
    case WITHDRAWAL_AMOUNT_TOO_LARGE = 50003; // مقدار برداشت خیلی زیاد است
    case DAILY_WITHDRAWAL_LIMIT_EXCEEDED = 50004; // محدودیت برداشت روزانه
    case WITHDRAWAL_ADDRESS_INVALID = 50005; // آدرس برداشت نامعتبر است
    case WITHDRAWAL_ADDRESS_NOT_WHITELISTED = 50006; // آدرس برداشت در لیست سفید نیست
    case WITHDRAWAL_TAG_REQUIRED = 50007; // برچسب برداشت مورد نیاز است
    case WITHDRAWAL_PROCESSING = 50008; // برداشت در حال پردازش است
    case WITHDRAWAL_ALREADY_COMPLETED = 50009; // برداشت قبلاً انجام شده است
    case WITHDRAWAL_ALREADY_CANCELED = 50010; // برداشت قبلاً لغو شده است
    
    // System Errors
    case INTERNAL_ERROR = 20002; // خطای داخلی
    case SYSTEM_BUSY = 20003; // سیستم مشغول است
    case SYSTEM_UPGRADE = 20004; // سیستم در حال ارتقا است
    case RATE_LIMIT_EXCEEDED = 20005; // محدودیت نرخ درخواست
    
    // Market Errors
    case MARKET_CLOSED = 30001; // بازار بسته است
    case TRADING_SUSPENDED = 30006; // معاملات معلق شده است
    case MARKET_NOT_FOUND = 30007; // بازار یافت نشد
    case PRICE_OUT_OF_RANGE = 30008; // قیمت خارج از محدوده است

    public static function mapErrorToResponse(MexcError $errorEnum): string
    {
        return match ($errorEnum) {
            // General Errors
            MexcError::UNKNOWN_ERROR => 'خطای ناشناخته رخ داده است.',
            MexcError::DISCONNECTED => 'ارتباط با سرور قطع شده است.',
            MexcError::UNAUTHORIZED => 'احراز هویت ناموفق.',
            MexcError::ACCESS_DENIED => 'دسترسی غیرمجاز.',
            MexcError::TOO_MANY_REQUESTS => 'تعداد درخواست بیش از حد مجاز است. لطفا بعدا تلاش کنید.',
            MexcError::SERVICE_UNAVAILABLE => 'سرویس در دسترس نیست. لطفا بعدا تلاش کنید.',
            
            // Authentication Errors
            MexcError::APIKEY_INVALID => 'کلید API نامعتبر است.',
            MexcError::SIGNATURE_INVALID => 'امضای درخواست نامعتبر است.',
            MexcError::TIMESTAMP_INVALID => 'زمان درخواست نامعتبر است.',
            MexcError::IP_RESTRICTED => 'آدرس IP شما مجاز به دسترسی نیست.',
            MexcError::CONTENT_TYPE_INVALID => 'نوع محتوا نامعتبر است.',
            
            // Parameter Errors
            MexcError::PARAM_ERROR => 'خطای پارامتر. لطفا پارامترهای درخواست را بررسی کنید.',
            MexcError::PARAM_EMPTY => 'پارامتر خالی است. لطفا مقدار را وارد کنید.',
            MexcError::PARAM_MISSING => 'پارامتر مورد نیاز وجود ندارد.',
            MexcError::PARAM_INVALID => 'پارامتر نامعتبر است.',
            
            // Order Errors
            MexcError::ORDER_MIN_AMOUNT => 'حداقل مقدار سفارش رعایت نشده است.',
            MexcError::ORDER_MAX_AMOUNT => 'مقدار سفارش بیش از حداکثر مجاز است.',
            MexcError::ORDER_PRICE_TOO_HIGH => 'قیمت سفارش بیش از حد مجاز است.',
            MexcError::ORDER_PRICE_TOO_LOW => 'قیمت سفارش کمتر از حد مجاز است.',
            MexcError::INSUFFICIENT_BALANCE => 'موجودی کافی نیست.',
            MexcError::SYMBOL_INVALID => 'نماد نامعتبر است.',
            MexcError::ORDER_TYPE_INVALID => 'نوع سفارش نامعتبر است.',
            MexcError::ORDER_SIDE_INVALID => 'جهت سفارش نامعتبر است.',
            MexcError::ORDER_NOT_FOUND => 'سفارش یافت نشد.',
            MexcError::ORDER_LOCKED => 'سفارش قفل شده است و قابل تغییر نیست.',
            MexcError::ORDER_PARTIALLY_FILLED => 'سفارش به صورت جزئی پر شده است.',
            MexcError::ORDER_FILLED => 'سفارش کاملاً پر شده است.',
            MexcError::ORDER_CANCELED => 'سفارش لغو شده است.',
            MexcError::ORDER_CANCELING => 'سفارش در حال لغو شدن است.',
            MexcError::DUPLICATE_ORDER => 'سفارش تکراری است.',
            MexcError::UNKNOWN_ORDER => 'سفارش ناشناخته است.',
            
            // Withdrawal Errors
            MexcError::WITHDRAWAL_SUSPENDED => 'برداشت در حال حاضر معلق شده است.',
            MexcError::WITHDRAWAL_AMOUNT_TOO_SMALL => 'مقدار برداشت کمتر از حداقل مجاز است.',
            MexcError::WITHDRAWAL_AMOUNT_TOO_LARGE => 'مقدار برداشت بیش از حداکثر مجاز است.',
            MexcError::DAILY_WITHDRAWAL_LIMIT_EXCEEDED => 'محدودیت برداشت روزانه شما تکمیل شده است.',
            MexcError::WITHDRAWAL_ADDRESS_INVALID => 'آدرس برداشت نامعتبر است.',
            MexcError::WITHDRAWAL_ADDRESS_NOT_WHITELISTED => 'آدرس برداشت در لیست سفید قرار ندارد.',
            MexcError::WITHDRAWAL_TAG_REQUIRED => 'برچسب برداشت مورد نیاز است.',
            MexcError::WITHDRAWAL_PROCESSING => 'برداشت در حال پردازش است.',
            MexcError::WITHDRAWAL_ALREADY_COMPLETED => 'برداشت قبلاً انجام شده است.',
            MexcError::WITHDRAWAL_ALREADY_CANCELED => 'برداشت قبلاً لغو شده است.',
            
            // System Errors
            MexcError::INTERNAL_ERROR => 'خطای داخلی. لطفا با پشتیبانی تماس بگیرید.',
            MexcError::SYSTEM_BUSY => 'سیستم مشغول است. لطفا بعدا تلاش کنید.',
            MexcError::SYSTEM_UPGRADE => 'سیستم در حال ارتقا است. لطفا بعدا تلاش کنید.',
            MexcError::RATE_LIMIT_EXCEEDED => 'محدودیت نرخ درخواست. لطفا سرعت درخواست‌ها را کاهش دهید.',
            
            // Market Errors
            MexcError::MARKET_CLOSED => 'بازار بسته است.',
            MexcError::TRADING_SUSPENDED => 'معاملات در این بازار معلق شده است.',
            MexcError::MARKET_NOT_FOUND => 'بازار مورد نظر یافت نشد.',
            MexcError::PRICE_OUT_OF_RANGE => 'قیمت خارج از محدوده مجاز است.',
            
            default => 'خطای ناشناخته، لطفا دوباره تلاش کنید.',
        };
    }
} 