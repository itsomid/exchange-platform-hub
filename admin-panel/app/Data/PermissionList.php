<?php

namespace App\Data;

class PermissionList
{
    const string PREFIX = '';

    public static function get(): array
    {
        $permissions = [
            ['admin.index', 'مشاهده لیست پرسنل'],
            ['admin.create', 'افزودن پرسنل جدید'],
            ['admin.edit', 'ویرایش پرسنل'],
            ['admin.toggle', 'مسدودسازی پرسنل'],
            ['admin.login-as-admin', 'ورود به عنوان ادمین'],

            ['roles.permissions', 'مدیریت نقش ها و مجوز ها'],

            ['transaction.index', 'مشاهده لیست تراکنش ها'],

            ['session.index', 'مشاهده نشست های فعال'],
            ['session.destroy', 'حذف نشست های فعال'],

            ['user.index', 'مشاهده صفحه لیست کاربران'],
            ['user.create', 'افزودن کاربر جدید'],
            ['user.edit', 'ویرایش کاربر'],
            ['user.edit-note', 'ویرایش یادداشت کاربر'],
            ['user.verify', 'تایید حساب کاربر'],
            ['user.excel', 'دانلود خروجی اکسل از لیست کاربران'],
            ['user.login-as-customer', 'ورود به عنوان کاربر'],

            ['referral_code.index', 'مشاهده کد های معرف'],
            ['referral_code.create', 'افزودن کد معرف'],
            ['referral_code.edit', 'ویرایش کد معرف'],

            ['setting.int.index', 'مشاهده تنظیمات داخلی'],
            ['setting.int.view-logs', 'مشاهده لاگ ها و خطاهای سیستم'],

            ['currency','مدیریت کوین ها'],
            ['market','مدیریت بازار'],
            ['ref-exchanges','مدیریت صرافی های مرجع'],
            ['wallet','مدیریت کیف پول ها'],

            ['transaction','مدیریت تراکنش ها'],
            ['otc_order', 'مشاهده لیست سفارش ها'],
            ['deposit', 'مشاهده لیست واریزی ها'],
            ['withdrawal', 'مشاهده لیست برداشت ها'],

            ['report','لیست گزارش ها'],
            ['view-logs','لیست ارورها'],

            ['notifications','لیست اعلان های مدیریت'],
            ['support','پشتیبانی کاربر'],
            ['viewTelescope','لاراول تلسکوپ'],
            ['viewPulse','لاراول پالس'],

        ];

        return array_map(fn($permission) => [$permission[0], $permission[1]], $permissions);
    }
}
