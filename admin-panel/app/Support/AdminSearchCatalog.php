<?php

namespace App\Support;

use App\Enums\TicketStatusEnum;
use App\Models\Admin;
use Illuminate\Support\Facades\Route;

class AdminSearchCatalog
{
    /**
     * Pages this admin is allowed to open. A route permission wins when the
     * route declares one; otherwise the sidebar gate is used. A locked panel
     * only keeps the account pages.
     *
     * @return array<int, array{id: string, title: string, group: string, url: string, icon: string, keywords: string, popular: int}>
     */
    public static function itemsFor(?Admin $admin, bool $locked): array
    {
        if (!$admin) {
            return [];
        }

        $items = [];

        foreach (self::definitions() as $item) {
            if ($locked && empty($item['account'])) {
                continue;
            }

            if (!empty($item['telescope']) && (app()->environment('production') || !Route::has('telescope'))) {
                continue;
            }

            if (!self::permitted($admin, $item['permission'] ?? null)) {
                continue;
            }

            $items[] = [
                'id' => $item['id'],
                'title' => $item['title'],
                'group' => $item['group'],
                'url' => isset($item['href'])
                    ? url($item['href'])
                    : route($item['route'], $item['params'] ?? []),
                'icon' => $item['icon'],
                'keywords' => $item['keywords'],
                'popular' => $item['popular'] ?? 0,
            ];
        }

        return $items;
    }

    private static function permitted(Admin $admin, mixed $permission): bool
    {
        if ($permission === null) {
            return true;
        }

        if (is_string($permission)) {
            return $admin->can($permission);
        }

        foreach ($permission['any'] ?? [] as $name) {
            if ($admin->can($name)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, array<string, mixed>> */
    private static function definitions(): array
    {
        return [
            self::page('dashboard', 'داشبورد', 'کلی', 'admin.dashboard', 'fa-regular fa-chart-pie-simple', 'dashboard خانه خانه اصلی', 80),
            self::page('financial-dashboard', 'داشبورد مالی', 'کلی', 'admin.financial-dashboard', 'fa-regular fa-chart-pie-simple', 'financial مالی درآمد سود', 68),
            self::page('accounting', 'پنل حسابداری', 'کلی', 'admin.accounting.sso', 'fa-regular fa-calculator', 'accounting حسابداری', 20, 'accounting.login'),

            self::page('admins', 'مدیریت همکاران', 'افراد و دپارتمان‌ها', 'admin.admin.index', 'fa-solid fa-user-tie-hair', 'admin ادمین همکار پرسنل کارمند', 60, 'admin.index'),
            self::page('users', 'مدیریت کاربران', 'افراد و دپارتمان‌ها', 'admin.user.index', 'fa-light fa-users', 'user users مشتری کاربران', 100, 'user.index'),
            self::page('blocked-users', 'کاربران مسدود شده', 'افراد و دپارتمان‌ها', 'admin.user.financial-status', 'fa-regular fa-user-lock', 'block مسدود بلاک مالی', 40, 'user.index'),
            self::page('locked-balances', 'دارایی مسدود شده', 'افراد و دپارتمان‌ها', 'admin.locked-balance.index', 'fa-regular fa-lock', 'locked balance دارایی قفل', 24, 'wallet'),
            self::page('inquiry', 'استعلام کاربر', 'افراد و دپارتمان‌ها', 'admin.inquiry.index', 'fa-light fa-user', 'inquiry استعلام احراز', 30, 'user.index'),
            self::page('referral-codes', 'کدهای معرف', 'مدیریت فروش', 'admin.referral_code.index', 'fa-regular fa-user-tag', 'referral معرف رفرال', 28, 'referral_code.index'),

            self::page('transactions', 'لیست تراکنش‌ها', 'مالی و خریدها', 'admin.transaction.index', 'fa-regular fa-chart-candlestick', 'transaction تراکنش', 92, 'transaction'),
            self::page('otc-orders', 'معاملات OTC', 'مالی و خریدها', 'admin.otc_orders.index', 'fa-regular fa-swap', 'otc سفارش خرید فروش', 96, 'otc_order'),
            self::page('spot-orders', 'سفارشات اسپات', 'مالی و خریدها', 'admin.spot_orders.index', 'fa-regular fa-chart-waterfall', 'spot order سفارش اسپات', 76, 'spot'),
            self::page('spot-trades', 'معاملات اسپات', 'مالی و خریدها', 'admin.spot_trades.index', 'fa-regular fa-chart-waterfall', 'spot trade معامله اسپات', 56, 'spot'),
            self::page('deposits', 'لیست واریزی‌ها', 'مالی و خریدها', 'admin.deposit.index', 'fa-regular fa-square-arrow-down-left', 'deposit واریز شارژ', 88, 'deposit'),
            self::page('withdrawals', 'لیست برداشت‌ها', 'مالی و خریدها', 'admin.withdrawal.index', 'fa-regular fa-square-arrow-up-right', 'withdrawal برداشت', 84, 'withdrawal'),
            self::page('stocks', 'مدیریت سهم‌ها', 'مالی و خریدها', 'admin.stock.index', 'fa-regular fa-chart-waterfall', 'stock سهم معجزه قرن', 18, 'stock'),
            self::page('stock-contracts', 'مدیریت قراردادها', 'مالی و خریدها', 'admin.stock-contract.index', 'fa-regular fa-chart-waterfall', 'contract قرارداد سهم', 12, 'stock'),

            self::page('currencies', 'Currency', 'مدیریت صرافی', 'admin.currency.index', 'fa-regular fa-circle-dollar', 'currency coin ارز کوین', 34, 'currency'),
            self::page('markets', 'Market', 'مدیریت صرافی', 'admin.market.index', 'fa-regular fa-shop', 'market بازار مارکت', 36, 'market'),
            self::page('ref-exchanges', 'مدیریت صرافی‌های مرجع', 'مدیریت صرافی', 'admin.exchange.index', 'fa-regular fa-display-chart-up-circle-dollar', 'exchange مرجع بایننس', 32, 'ref-exchanges'),
            self::page('ref-spot-orders', 'سفارش‌های اسپات صرافی مرجع', 'مدیریت صرافی', 'admin.ref-exchange.spot-orders.index', 'fa-regular fa-book-open-cover', 'ref spot سفارش مرجع', 22, 'ref-exchanges'),
            self::exchangeWallet('ref-binance', 'صرافی مرجع (Binance)', 'binance', 'binance بایننس'),
            self::exchangeWallet('ref-coinex', 'صرافی مرجع (Coinex)', 'coinex', 'coinex کوینکس'),
            self::exchangeWallet('ref-mexc', 'صرافی مرجع (Mexc)', 'mexc', 'mexc'),

            self::ticket('tickets-open', 'تیکت‌های جدید', TicketStatusEnum::OPEN->value, 'fa-regular fa-headset', 72),
            self::ticket('tickets-all', 'همه تیکت‌ها', null, 'fa-light fa-ticket', 26),
            self::ticket('tickets-active', 'تیکت‌های فعال', TicketStatusEnum::InProgress->value, 'fa-light fa-ticket', 20),
            self::ticket('tickets-closed', 'تیکت‌های بسته شده', TicketStatusEnum::CLOSED->value, 'fa-light fa-ticket', 8),

            self::page('hot-wallets', 'Hot Wallet', 'کیف پول‌ها', 'admin.exchange.hot-wallet', 'fa-regular fa-wallet', 'hot wallet کیف پول داغ', 16, 'wallet'),
            self::page('local-wallets', 'کیف پول داخلی', 'کیف پول‌ها', 'admin.exchange.local-wallet', 'fa-regular fa-wallet', 'local wallet داخلی', 14, 'wallet'),
            self::page('asset-gathering', 'سابقه تجمیع HDWallet', 'کیف پول‌ها', 'admin.ref-exchange.assets-gathering-to-hd-wallet.index', 'fa-regular fa-wallet', 'gather تجمیع hdwallet', 10, 'wallet'),
            self::page('asset-gathering-pending', 'تجمیع‌های تکمیل نشده', 'کیف پول‌ها', 'admin.ref-exchange.assets-gathering-to-hd-wallet.pending-withdrawal', 'fa-regular fa-wallet', 'pending تجمیع ناتمام', 8, 'wallet'),
            self::page('hd-wallet', 'گزارش موجودی HD Wallet', 'کیف پول‌ها', 'admin.hd-wallet.index', 'fa-regular fa-wallet', 'hd wallet موجودی', 18, 'hd_wallet'),
            self::page('hd-wallet-currencies', 'ارزهای HD Wallet', 'کیف پول‌ها', 'admin.hd-wallet.currencies', 'fa-regular fa-wallet', 'hd currency ارز', 8, 'hd_wallet'),
            self::page('sweeper-transactions', 'تراکنش‌های Sweeper', 'کیف پول‌ها', 'admin.hd-wallet.sweeper-transactions', 'fa-regular fa-arrows-rotate', 'sweeper سویپر', 8, 'hd_wallet'),

            self::page('api-systems', 'لیست سیستم‌های API', 'مدیریت API', 'admin.api-system.index', 'fa-regular fa-code', 'api system سیستم', 14, 'api-system'),
            self::page('api-systems-create', 'افزودن سیستم API', 'مدیریت API', 'admin.api-system.create', 'fa-light fa-plus', 'api create سیستم جدید', 4, 'api-system'),
            self::page('api-systems-stats', 'آمار سیستم‌های API', 'مدیریت API', 'admin.api-system.statistics', 'fa-light fa-chart-bar', 'api statistics آمار', 6, 'api-system'),

            self::page('bot-settings', 'تنظیمات توسل سیستم', 'ربات', 'admin.bot.settings.index', 'fa-light fa-sliders', 'bot setting ربات تنظیمات', 20, 'bot-management'),
            self::page('bot-signals', 'سیگنال‌ها', 'ربات', 'admin.bot.signal.index', 'fa-light fa-signal', 'signal سیگنال ربات', 22, 'bot-management'),
            self::page('bot-orders', 'سفارش‌های ربات', 'ربات', 'admin.bot.order.index', 'fa-light fa-list-check', 'bot order سفارش ربات', 18, 'bot-management'),
            self::page('bot-currencies', 'ارزهای ربات', 'ربات', 'admin.bot.currency.index', 'fa-light fa-coins', 'bot currency ارز ربات', 10, 'bot-management'),
            self::page('bot-sell-migration', 'انتقال پله‌های فروش', 'ربات', 'admin.bot.sell-migration.index', 'fa-light fa-arrow-right-arrow-left', 'migration انتقال فروش', 8, 'bot-management'),
            self::page('bot-waiting-sells', 'پله‌های در انتظار ثبت', 'ربات', 'admin.bot.waiting-sells.index', 'fa-light fa-hourglass-half', 'waiting sell انتظار', 8, 'bot-management'),
            self::page('bot-wallet-transfers', 'واریز و برداشت ربات', 'ربات', 'admin.bot.wallet-transfer.index', 'fa-light fa-right-left', 'bot transfer واریز برداشت', 8, 'bot-management'),
            self::page('bot-reports', 'گزارش‌های ربات', 'ربات', 'admin.bot.report.index', 'fa-light fa-chart-line', 'bot report گزارش ربات', 8, 'bot-management'),
            self::page('bot-test-lab', 'آزمایشگاه تست', 'ربات', 'admin.bot.test-lab.index', 'fa-light fa-flask', 'test lab آزمایشگاه', 4, 'bot-management'),
            self::page('spot-bot', 'بات اسپات', 'ربات', 'admin.setting.spot-bot.index', 'fa-regular fa-robot', 'spot bot بات اسپات', 16, 'setting.int.index'),

            self::page('report-deposits', 'گزارش واریز', 'گزارشات', 'admin.report.deposit', 'fa-solid fa-chart-line-up', 'report deposit گزارش واریز', 24, 'report'),
            self::page('report-withdrawals', 'گزارش برداشت', 'گزارشات', 'admin.report.withdrawal', 'fa-solid fa-chart-line-down', 'report withdrawal گزارش برداشت', 24, 'report'),
            self::page('report-registrations', 'گزارش ثبت‌نام کاربران', 'گزارشات', 'admin.report.getUserRegistrationState', 'fa-solid fa-chart-user', 'registration ثبت نام کاربر', 20, 'user.index'),
            self::page('report-ref-buys', 'گزارش خرید از صرافی مرجع', 'گزارشات', 'admin.report.ref-exchange.bought-history', 'fa-regular fa-display-chart-up-circle-dollar', 'bought خرید مرجع', 12, 'report'),

            self::page('notifications', 'اعلان‌های مدیریت', 'مدیریت سیستم', 'admin.admin.notifications.index', 'fa-regular fa-bell', 'notification اعلان پیام', 18, 'notifications'),
            self::page('roles', 'نقش‌ها', 'مدیریت سیستم', 'admin.role.index', 'fa-regular fa-user-group', 'role نقش', 14, 'roles.permissions'),
            self::page('permissions', 'مجوزها', 'مدیریت سیستم', 'admin.permission.index', 'fa-light fa-key', 'permission مجوز دسترسی', 14, 'roles.permissions'),
            self::page('internal-settings', 'تنظیمات داخلی', 'مدیریت سیستم', 'admin.internal.setting.index', 'fa-regular fa-cog', 'setting تنظیمات داخلی', 16, 'setting.int.index'),
            self::page('docker', 'کانتینرهای داکر', 'مدیریت سیستم', 'admin.docker.index', 'fa-regular fa-cog', 'docker داکر کانتینر', 6, 'setting.int.index'),
            [
                'id' => 'telescope',
                'title' => 'تلسکوپ',
                'group' => 'مدیریت سیستم',
                'route' => 'telescope',
                'icon' => 'fa-light fa-telescope',
                'keywords' => 'telescope تلسکوپ',
                'popular' => 2,
                'permission' => 'view-logs',
                'telescope' => true,
            ],
            [
                'id' => 'logs',
                'title' => 'Logs and Errors',
                'group' => 'مدیریت سیستم',
                'href' => '/log-viewer',
                'icon' => 'fa-regular fa-circle-exclamation',
                'keywords' => 'log error لاگ خطا',
                'popular' => 4,
                'permission' => 'view-logs',
            ],

            self::page('profile', 'اطلاعات کاربری', 'حساب کاربری', 'admin.profile.edit', 'fa-regular fa-user', 'profile پروفایل حساب', 6, null, true),
            self::page('profile-password', 'ویرایش گذرواژه', 'حساب کاربری', 'admin.profile.password.edit', 'fa-regular fa-lock', 'password گذرواژه رمز', 6, null, true),
            self::page('profile-2fa', 'احراز هویت دو مرحله‌ای', 'حساب کاربری', 'admin.profile.2fa.edit', 'fa-brands fa-google', '2fa دو مرحله ای گوگل', 6, null, true),
        ];
    }

    private static function page(
        string $id,
        string $title,
        string $group,
        string $route,
        string $icon,
        string $keywords,
        int $popular = 0,
        ?string $permission = null,
        bool $account = false,
    ): array {
        return [
            'id' => $id,
            'title' => $title,
            'group' => $group,
            'route' => $route,
            'icon' => $icon,
            'keywords' => $keywords,
            'popular' => $popular,
            'permission' => $permission,
            'account' => $account,
        ];
    }

    private static function exchangeWallet(string $id, string $title, string $exchange, string $keywords): array
    {
        return [
            'id' => $id,
            'title' => $title,
            'group' => 'مدیریت صرافی',
            'route' => 'admin.exchange.wallet',
            'params' => ['exchange' => $exchange],
            'icon' => 'fa-light fa-user',
            'keywords' => 'exchange wallet مرجع '.$keywords,
            'popular' => 10,
            'permission' => ['any' => ['currency', 'market', 'ref-exchanges', 'ref-exchanges-spot-orders']],
        ];
    }

    private static function ticket(string $id, string $title, ?string $status, string $icon, int $popular): array
    {
        return [
            'id' => $id,
            'title' => $title,
            'group' => 'ارتباط با کاربر',
            'route' => 'admin.tickets.index',
            'params' => $status ? ['status' => $status] : [],
            'icon' => $icon,
            'keywords' => 'ticket support تیکت پشتیبانی',
            'popular' => $popular,
            'permission' => 'support',
        ];
    }
}
