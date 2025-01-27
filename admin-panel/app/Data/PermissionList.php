<?php

namespace App\Data;

class PermissionList
{
    const string PREFIX = '';

    public static function get(): array
    {
        $permissions = [
            ['admin.index', 'مشاهده لیست پرسنل'],
            ['admin.index.statistic_boxes', 'مشاهده باکس آمار در لیست پرسنل'],
            ['admin.index.table.mobile', 'مشاهده  ستون شماره تماس ها در لیست پرسنل'],
            ['admin.index.table.email', 'مشاهده  ستون آدرس ایمیل ها در لیست پرسنل'],
            ['admin.index.table.supervisor', 'مشاهده  ستون سرپرست ها در لیست پرسنل'],
            ['admin.create', 'افزودن پرسنل جدید'],
            ['admin.edit', 'ویرایش پرسنل'],
            ['admin.toggle', 'مسدودسازی پرسنل'],
            ['admin.manage-all-support', 'مشاهده تمام پشتیبانان'],
            ['admin.login-as-admin', 'ورود به عنوان ادمین'],
            ['admin.inquiry', 'استعلام شماره تماس'],

            ['role.admin.edit', 'ویرایش نقش پرسنل'],
            ['role.index', 'لیست نقش ها'],
            ['role.create', 'افزودن نقش'],
            ['role.edit', 'ویرایش نقش'],
            ['role.destroy', 'حذف نقش'],
            ['permission.index', 'مشاهده لیست مجوز ها'],
            ['permission.edit', 'ویرایش مجوز'],

            ['transaction.index', 'مشاهده لیست تراکنش ها'],

            ['session.index', 'مشاهده نشست های فعال'],
            ['session.destroy', 'حذف نشست های فعال'],

            ['user.index', 'مشاهده صفحه لیست کاربران'],
            ['user.create', 'افزودن کاربر جدید'],
            ['user.edit', 'ویرایش کاربر'],
            ['user.edit-note', 'ویرایش یادداشت کاربر'],
            ['user.verify', 'تایید شماره کاربر'],
            ['user.excel', 'دانلود خروجی اکسل از لیست کاربران'],
            ['user.group-register', 'ثبت نام گروهی کاربر'],
            ['user.login-as-customer', 'ورود به عنوان کاربر'],

            ['referral_code.index', 'مشاهده کد های معرف'],
            ['referral_code.create', 'افزودن کد معرف'],
            ['referral_code.edit', 'ویرایش کد معرف'],

            ['setting.int.index', 'مشاهده تنظیمات داخلی'],
            ['setting.int.view-logs', 'مشاهده لاگ ها و خطاهای سیستم'],
            ['setting.ext.index', 'مشاهده تنظیمات خارجی'],

            ['currency','مدیریت کوین ها'],
            ['market','مدیریت بازار'],
            ['ref-exchanges','مدیریت صرافی های مرجع'],
            ['wallet','مدیریت کیف پول ها'],

            ['transaction','مدیریت تراکنش ها'],
            ['otc_order', 'مشاهده لیست سفارش ها'],
            ['deposit', 'مشاهده لیست واریزی ها'],
            ['withdrawal', 'مشاهده لیست برداشت ها'],

            ['report','لیست گزارش ها']

        ];

        return array_map(fn($permission) => [$permission[0], $permission[1]], $permissions);
    }
}
