<?php

namespace App\Enums;

enum ApiRequestType: string
{
    case USER_BALANCE = 'user_balance'; // Check user balance
    case USER_INQUIRY = 'user_inquiry'; // Check if user exists in system
    case USER_CREDIT_INCREASE = 'user_credit_increase'; // Increase user credit
    case STOCK_PURCHASE = 'stock_purchase';
    case TRANSACTION_HISTORY = 'transaction_history';
    case TRACKING_CODE_GENERATION = 'tracking_code_generation'; // Generate tracking code


    /**
     * Get the display name for the request type
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::USER_BALANCE => 'استعلام موجودی کاربر',
            self::USER_INQUIRY => 'استعلام وجود کاربر',
            self::USER_CREDIT_INCREASE => 'افزایش اعتبار کاربر',
            self::STOCK_PURCHASE => 'درخواست خرید سهام',
            self::TRANSACTION_HISTORY => 'تاریخچه تراکنش‌ها',
            self::TRACKING_CODE_GENERATION => 'تولید کد رهگیری',
        };
    }

    /**
     * Get the description for the request type
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::USER_BALANCE => 'درخواست برای چک کردن موجودی فعلی کاربر',
            self::USER_INQUIRY => 'درخواست برای بررسی وجود کاربر در سیستم',
            self::USER_CREDIT_INCREASE => 'درخواست برای افزایش اعتبار کاربر با ثبت تراکنش واریز',
            self::STOCK_PURCHASE => 'درخواست برای خرید سهام با مقدار مشخص',
            self::TRANSACTION_HISTORY => 'درخواست برای دریافت تاریخچه تراکنش‌های کاربر',
            self::TRACKING_CODE_GENERATION => 'درخواست برای تولید کد رهگیری جهت پیگیری درخواست‌های بعدی',
        };
    }


    /**
     * Get all available request types
     */
    public static function getAll(): array
    {
        return [
            self::USER_BALANCE,
            self::USER_INQUIRY,
            self::USER_CREDIT_INCREASE,
            self::STOCK_PURCHASE,
            self::TRANSACTION_HISTORY,
            self::TRACKING_CODE_GENERATION,
        ];
    }

    /**
     * Get request types as array for select options
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::getAll() as $type) {
            $options[$type->value] = $type->getDisplayName();
        }
        return $options;
    }

    /**
     * Get all values as array
     */
    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
